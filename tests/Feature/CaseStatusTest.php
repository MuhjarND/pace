<?php

namespace Tests\Feature;

use App\Services\CaseStatusLookup;
use App\WorkUnit;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaseStatusTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kasuari.case_status_url'=>'https://kasuari.example.go.id/public_case_status',
            'services.kasuari.token'=>str_repeat('s', 64)]);
        $this->fake($this->payload());
    }

    private function unit() { return WorkUnit::where('name', 'Pengadilan Agama Sorong')->firstOrFail(); }
    private function payload($extra = [])
    {
        return array_merge(['version'=>1, 'state'=>'found', 'court_key'=>'sorong', 'synced_at'=>'2026-11-01T10:30:00+09:00',
            'case'=>['number'=>'123/Pdt.G/2026/PA.Srg', 'type'=>'Cerai Gugat', 'stage'=>'Persidangan',
                'process'=>'Penetapan hari sidang', 'registered_on'=>'2026-10-20', 'decided_on'=>null,
                'pihak1_text'=>'NAMA RAHASIA', 'nik'=>'NIK RAHASIA', 'petitum'=>'DOKUMEN RAHASIA'],
            'telepon'=>'KONTAK RAHASIA'], $extra);
    }
    private function fake($payload, $status = 200)
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*'=>new FulfilledPromise(new Response($status, ['Content-Type'=>'application/json'], json_encode($payload)))]);
    }
    private function search($extra = [])
    {
        return $this->from('/cek-status-perkara')->post('/cek-status-perkara', array_merge([
            'work_unit_id'=>$this->unit()->id, 'case_number'=>'123/Pdt.G/2026/PA.Srg'], $extra));
    }

    public function test_public_form_has_two_user_inputs_and_links_from_portal()
    {
        $this->get('/cek-status-perkara')->assertOk()->assertSee('Cek status perkara')->assertSee('name="case_number"', false)
            ->assertSee('name="work_unit_id"', false)->assertHeader('Cache-Control', 'no-store, private')->assertDontSee(str_repeat('s',64));
        $this->get('/cari-pengadilan')->assertOk()->assertSee(route('public.cases'));
        $this->assertGuest(); Http::assertNothingSent();
    }
    public function test_lookup_is_exact_normalized_and_redacts_upstream_extra_fields()
    {
        $this->search(['case_number'=>' 123 / Pdt.G / 2026 / PA.Srg '])->assertOk()->assertSee('Persidangan')
            ->assertSee('01/11/2026 10:30 WIT')->assertSee('20/10/2026')->assertSee('Belum tercatat pada data yang tersedia')
            ->assertDontSee('NAMA RAHASIA')->assertDontSee('NIK RAHASIA')->assertDontSee('DOKUMEN RAHASIA')->assertDontSee('KONTAK RAHASIA');
        Http::assertSent(function ($request) {
            return $request->url() === config('services.kasuari.case_status_url') && $request['court'] === 'Pengadilan Agama Sorong'
                && $request['case_number'] === '123/PDT.G/2026/PA.SRG' && $request->hasHeader('X-PTSP-Key', str_repeat('s',64));
        });
        $this->assertGuest();
    }
    public function test_not_found_is_distinct_from_upstream_outage()
    {
        $this->fake($this->payload(['state'=>'not_found','case'=>null]));
        $this->search()->assertOk()->assertSee('Perkara belum ditemukan')->assertDontSee('Status belum dapat dimuat');
        $this->fake($this->payload(), 503);
        $this->search()->assertStatus(503)->assertSee('Status belum dapat dimuat')->assertDontSee('Perkara belum ditemukan')
            ->assertSee('value="123/Pdt.G/2026/PA.Srg"', false);
    }
    public function test_missing_config_fails_closed_without_network_request()
    {
        config(['services.kasuari.token'=>null]);
        $this->search()->assertStatus(503)->assertSee('Status belum dapat dimuat'); Http::assertNothingSent();
    }
    public function test_unsafe_plain_http_endpoint_is_not_used()
    {
        config(['services.kasuari.case_status_url'=>'http://external.example.go.id/public_case_status']);
        $this->search()->assertStatus(503); Http::assertNothingSent();
    }
    public function test_mismatched_case_or_court_and_bad_payload_are_rejected()
    {
        foreach ([$this->payload(['court_key'=>'manokwari']), $this->payload(['case'=>['number'=>'124/Pdt.G/2026/PA.Srg']]),
            ['version'=>1,'state'=>'found','case'=>null], ['state'=>'not_found'], ['version'=>1,'state'=>'anything']] as $payload) {
            $this->fake($payload); $this->search()->assertStatus(503)->assertSee('Status belum dapat dimuat');
        }
    }
    public function test_transport_and_non_json_errors_do_not_expose_internal_details()
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*'=>new RejectedPromise(new \RuntimeException('SECRET CONNECTION DATA'))]);
        $this->search()->assertStatus(503)->assertDontSee('SECRET CONNECTION DATA');
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*'=>new FulfilledPromise(new Response(200, [], '<html>PRIVATE SQL ERROR</html>'))]);
        $this->search()->assertStatus(503)->assertDontSee('PRIVATE SQL ERROR');
    }
    public function test_validation_rejects_broad_search_and_inactive_or_non_court_units()
    {
        foreach (['123','nama orang',"123' OR 1=1",'123/Pdt.G/2026/PA.Srg%'] as $number) {
            $this->search(['case_number'=>$number])->assertRedirect('/cek-status-perkara')->assertSessionHasErrors('case_number');
        }
        $this->unit()->update(['is_active'=>false]);
        $this->search()->assertSessionHasErrors('work_unit_id'); Http::assertNothingSent();
    }
    public function test_unknown_unit_and_array_inputs_are_rejected()
    {
        $this->search(['work_unit_id'=>99999])->assertSessionHasErrors('work_unit_id');
        $this->search(['case_number'=>['bad']])->assertSessionHasErrors('case_number'); Http::assertNothingSent();
    }
    public function test_absent_metadata_and_invalid_dates_are_not_invented()
    {
        $payload = $this->payload(['synced_at'=>null]);
        $payload['case']['registered_on']='2026-02-31'; $payload['case']['decided_on']='0000-00-00';
        $this->fake($payload);
        $this->search()->assertOk()->assertSee('Waktu sinkronisasi satker belum tersedia.')->assertDontSee('31/02/2026');
    }
    public function test_consultation_carries_the_selected_court_and_case_topic()
    {
        $response = $this->search();
        $url = route('public.consult', ['unit'=>$this->unit()->id,'case_number'=>'123/PDT.G/2026/PA.SRG']);
        $response->assertSee(e($url), false);
        $this->get($url)->assertOk()->assertSee('Pertanyaan status perkara dan akta cerai 123/PDT.G/2026/PA.SRG')
            ->assertSee('value="'.$this->unit()->id.'" selected', false);
    }
    private function certificate($extra = [])
    {
        return array_merge(['state'=>'issued','issued_on'=>'2026-10-30','synced_at'=>'2026-11-01T11:30:00+09:00',
            'collections'=>['party1'=>['state'=>'collected','collected_on'=>'2026-10-31'],
                'party2'=>['state'=>'not_recorded','collected_on'=>null]],
            'nomor_akta_cerai'=>'PRIVATE CERTIFICATE NUMBER','nama'=>'PRIVATE CERTIFICATE PARTY',
            'akta_cerai_dok'=>'PRIVATE CERTIFICATE FILE'], $extra);
    }

    public function test_combined_menu_and_independent_collection_status_are_public_and_redacted()
    {
        $this->fake($this->payload(['divorce_certificate'=>$this->certificate()]));
        $this->get('/cari-pengadilan')->assertOk()->assertSee('Cek status perkara dan akta cerai');
        $this->search()->assertOk()->assertSee('Akta terbit tercatat')->assertSee('Pihak 1')->assertSee('Pihak 2')
            ->assertSee('Sudah diambil (tercatat)')->assertSee('Pengambilan belum tercatat')->assertSee('31/10/2026')
            ->assertSee('30/10/2026')->assertSee('01/11/2026 11:30 WIT')->assertSee('01/11/2026 10:30 WIT')
            ->assertDontSee('PRIVATE CERTIFICATE NUMBER')->assertDontSee('PRIVATE CERTIFICATE PARTY')->assertDontSee('PRIVATE CERTIFICATE FILE');
        $this->assertGuest();
    }

    public function test_missing_certificate_metadata_does_not_break_case_or_claim_it_is_uncollected()
    {
        $this->search()->assertOk()->assertSee('Persidangan')->assertSee('Informasi akta cerai belum tersedia')
            ->assertDontSee('Akta terbit tercatat')->assertDontSee('Belum diambil');
        $this->fake($this->payload(['divorce_certificate'=>$this->certificate(['state'=>'unavailable'])]));
        $this->search()->assertOk()->assertSee('Informasi akta cerai belum tersedia')->assertDontSee('Sudah diambil (tercatat)');
    }

    public function test_incomplete_and_empty_published_certificate_snapshot_are_distinct()
    {
        $this->fake($this->payload(['divorce_certificate'=>$this->certificate(['state'=>'incomplete','issued_on'=>null])]));
        $this->search()->assertOk()->assertSee('Data akta belum lengkap')->assertDontSee('Akta terbit tercatat');
        $this->fake($this->payload(['divorce_certificate'=>$this->certificate(['state'=>'not_recorded','issued_on'=>null,'collections'=>[]])]));
        $this->search()->assertOk()->assertSee('Akta belum tercatat pada data tersinkron')->assertDontSee('Akta terbit tercatat');
    }

    public function test_unavailable_party_two_is_not_described_as_uncollected_or_downloaded()
    {
        $data=$this->certificate(); $data['collections']['party2']=['state'=>'unavailable','collected_on'=>'2026-10-31'];
        $this->fake($this->payload(['divorce_certificate'=>$data]));
        $result=app(CaseStatusLookup::class)->lookup($this->unit(),'123/PDT.G/2026/PA.SRG');
        $this->assertSame(['state'=>'unavailable','collected_on'=>null],$result['certificate']['collections']['party2']);
        $this->search()->assertOk()->assertSee('Informasi pengambilan belum tersedia')->assertDontSee('Belum diambil')->assertDontSee('Sudah diunduh');
    }

    public function test_invalid_certificate_dates_and_missing_sync_cannot_create_confirmed_status()
    {
        $data=$this->certificate(['issued_on'=>'2026-02-31']); $data['collections']['party1']['collected_on']='2026-02-31';
        $this->fake($this->payload(['divorce_certificate'=>$data]));
        $this->search()->assertOk()->assertSee('Data akta belum lengkap')->assertDontSee('Akta terbit tercatat')
            ->assertDontSee('Sudah diambil (tercatat)')->assertDontSee('31/02/2026');
        $this->fake($this->payload(['divorce_certificate'=>$this->certificate(['synced_at'=>null])]));
        $this->search()->assertOk()->assertSee('Persidangan')->assertSee('Informasi akta cerai belum tersedia');
    }

    public function test_non_divorce_case_without_certificate_has_no_collection_panel()
    {
        $data=$this->payload(); $data['case']['type']='Perwalian';
        $this->fake($data);
        $this->search()->assertOk()->assertSee('Perwalian')->assertDontSee('Penerbitan dan pengambilan');
    }

    public function test_lookups_are_rate_limited_with_recovery_and_no_extra_request()
    {
        for ($i=0; $i<10; $i++) $this->search()->assertOk();
        $this->search()->assertStatus(429)->assertHeader('Retry-After')->assertSee('Tunggu sebentar sebelum mengecek lagi');
        $this->assertCount(10, Http::recorded(function () { return true; }));
    }
}

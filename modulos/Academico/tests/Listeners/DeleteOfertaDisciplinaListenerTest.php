<?php

use Modulos\Integracao\Events\TurmaMapeadaEvent;
use Modulos\Academico\Events\CreateOfertaDisciplinaEvent;
use Modulos\Academico\Events\DeleteOfertaDisciplinaEvent;

/**
 * Class DeleteOfertaDisciplinaListenerTest
 * @group Listeners
 */
class DeleteOfertaDisciplinaListenerTest extends TestCase
{
    protected $turma;
    protected $ambiente;
    protected $ofertaDisciplina;
    protected $sincronizacaoRepository;

    public function createApplication()
    {
        putenv('DB_CONNECTION=sqlite_testing');

        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function setUp()
    {
        parent::setUp();

        Artisan::call('modulos:migrate');

        $this->sincronizacaoRepository = $this->app->make(\Modulos\Integracao\Repositories\SincronizacaoRepository::class);

        Modulos\Integracao\Models\Servico::truncate();

        $this->createAmbiente();
        $this->createIntegracao();
        $this->createMonitor();
        $this->mockUpDatabase();
    }

    /**
     * Cria um ambiente de testes a partir das variaveis de ambiente
     *
     * @return void
     */
    private function createAmbiente()
    {
        $moodle = [
            'amb_nome' => 'Moodle',
            'amb_versao' => '3.2+',
            'amb_url' => "http://localhost:8080"
        ];

        $this->ambiente = factory(Modulos\Integracao\Models\AmbienteVirtual::class)->create($moodle);
    }

    /**
     * Cria o registro do plugin de integracao a partir das variaveis de ambiente
     *
     * @return void
     */
    private function createIntegracao()
    {
        $servico = factory(Modulos\Integracao\Models\Servico::class)->create([
            'ser_id' => 2,
            'ser_nome' => "Integração",
            'ser_slug' => "local_integracao"
        ]);

        $ambienteServico = factory(\Modulos\Integracao\Models\AmbienteServico::class)->create([
            'asr_amb_id' => $this->ambiente->amb_id,
            'asr_ser_id' => $servico->ser_id,
            'asr_token' => "aksjhdeuig2768125sahsjhdvjahsy"
        ]);
    }

    /**
     * Cria o registro do plugin de monitoramento a partir das variaveis de ambiente
     *
     * @return void
     */
    private function createMonitor()
    {
        $servico = factory(Modulos\Integracao\Models\Servico::class)->create([
            'ser_nome' => "Monitor",
            'ser_slug' => "get_tutor_online_time"
        ]);

        $ambienteServico = factory(\Modulos\Integracao\Models\AmbienteServico::class)->create([
            'asr_amb_id' => $this->ambiente->amb_id,
            'asr_ser_id' => $servico->ser_id,
            'asr_token' => "abcdefgh12345"
        ]);
    }


    /**
     * Fazer mock-up de todas as etapas necessarias para se mapear uma turma em um ambiente virtual
     *
     * @return void
     */
    private function mockUpDatabase()
    {
        // Cria a turma
        $data = [
            'trm_id' => random_int(50, 100),
            'trm_ofc_id' => factory(Modulos\Academico\Models\OfertaCurso::class)->create()->ofc_id,
            'trm_per_id' => factory(Modulos\Academico\Models\PeriodoLetivo::class)->create()->per_id,
            'trm_nome' => "Turma de Teste",
            'trm_integrada' => 1,
            'trm_qtd_vagas' => 50
        ];


        $this->turma = factory(Modulos\Academico\Models\Turma::class)->create($data);

        // Cria a oferta
        $ofertaDisciplinaData =  [
            'ofd_trm_id' => $this->turma->trm_id,
            'ofd_per_id' => $this->turma->trm_per_id,
        ];

        $this->ofertaDisciplina = factory(Modulos\Academico\Models\OfertaDisciplina::class)->create($ofertaDisciplinaData);

        // Vincular com o ambiente
        factory(\Modulos\Integracao\Models\AmbienteTurma::class)->create([
            'atr_trm_id' => $this->turma->trm_id,
            'atr_amb_id' => $this->ambiente->amb_id
        ]);

        // Mapeia a turma
        $sincronizacaoListener = $this->app->make(\Modulos\Integracao\Listeners\SincronizacaoListener::class);
        $turmaMapeadaListener = $this->app->make(\Modulos\Integracao\Listeners\TurmaMapeadaListener::class);
        $createOfertaDisciplinaListener = $this->app->make(\Modulos\Academico\Listeners\CreateOfertaDisciplinaListener::class);

        $turmaMapeadaEvent = new TurmaMapeadaEvent($this->turma);

        $sincronizacaoListener->handle($turmaMapeadaEvent);
        $turmaMapeadaListener->handle($turmaMapeadaEvent);

        $createOfertaDisciplinaEvent = new CreateOfertaDisciplinaEvent($this->ofertaDisciplina);
        $sincronizacaoListener->handle($createOfertaDisciplinaEvent);
        $createOfertaDisciplinaListener->handle($createOfertaDisciplinaEvent);
    }

    public function testHandle()
    {
        $sincronizacaoListener = $this->app->make(\Modulos\Integracao\Listeners\SincronizacaoListener::class);
        $deleteOfertaDisciplinaListener = $this->app->make(\Modulos\Academico\Listeners\DeleteOfertaDisciplinaListener::class);

        $this->assertEquals(2, $this->sincronizacaoRepository->count());

        $deleteOfertaDisciplinaEvent = new DeleteOfertaDisciplinaEvent($this->ofertaDisciplina, $this->ambiente->amb_id);

        $sincronizacaoListener->handle($deleteOfertaDisciplinaEvent);

        $this->assertDatabaseHas('int_sync_moodle', [
            'sym_table' => $deleteOfertaDisciplinaEvent->getData()->getTable(),
            'sym_table_id' => $deleteOfertaDisciplinaEvent->getData()->getKey(),
            'sym_action' => $deleteOfertaDisciplinaEvent->getAction(),
            'sym_status' => 1,
            'sym_mensagem' => null,
            'sym_data_envio' => null,
            'sym_extra' => $deleteOfertaDisciplinaEvent->getExtra()
        ]);


        $this->expectsEvents(\Modulos\Integracao\Events\UpdateSincronizacaoEvent::class);
        $deleteOfertaDisciplinaListener->handle($deleteOfertaDisciplinaEvent);

        $this->assertEquals(3, $this->sincronizacaoRepository->count());
    }
}

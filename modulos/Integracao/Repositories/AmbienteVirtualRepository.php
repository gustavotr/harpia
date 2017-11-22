<?php
declare(strict_types=1);

namespace Modulos\Integracao\Repositories;

use DB;
use Modulos\Academico\Repositories\TurmaRepository;
use Modulos\Core\Repository\BaseRepository;
use Modulos\Integracao\Models\AmbienteVirtual;

class AmbienteVirtualRepository extends BaseRepository
{
    protected $turmaRepository;

    public function __construct(AmbienteVirtual $ambientevirtual, TurmaRepository $turmaRepository)
    {
        parent::__construct($ambientevirtual);
        $this->turmaRepository = $turmaRepository;
    }

    public function getAmbienteByTurma(int $turmaId)
    {
        $turma = $this->turmaRepository->find($turmaId);
        return $turma->ambientes->first();
    }

    /**
     * TODO 1 ocorrencia para refatorar
     */
    public function findTurmasWithoutAmbiente($ofertaId)
    {
        $turmasvinculadas = DB::table('int_ambientes_turmas')
            ->get();

        $turmasvinculadasId = [];

        foreach ($turmasvinculadas as $key => $value) {
            $turmasvinculadasId[] = $value->atr_trm_id;
        }

        $turmas = DB::table('acd_turmas')
            ->whereNotIn('trm_id', $turmasvinculadasId)
            ->where('trm_ofc_id', '=', $ofertaId)
            ->where('trm_integrada', '=', 1)
            ->get();

        return $turmas;
    }

    /**
     * TODO 2 ocorrencias para refatorar
     * @return mixed
     */
    public function findAmbientesWithMonitor()
    {
        $entries = DB::table('int_ambientes_virtuais')
            ->join('int_ambientes_servicos', 'asr_amb_id', '=', 'amb_id')
            ->join('int_servicos', 'asr_ser_id', '=', 'ser_id')
            ->where('ser_id', '=', 1)
            ->get();
        
        return $entries;
    }

    /**
     * TODO 2 ocorrencias para refatorar
     * @param $ambienteId
     * @return mixed
     */
    public function findAmbienteWithMonitor($ambienteId)
    {
        $entries = DB::table('int_ambientes_virtuais')
            ->join('int_ambientes_servicos', 'asr_amb_id', '=', 'amb_id')
            ->join('int_servicos', 'asr_ser_id', '=', 'ser_id')
            ->where('ser_id', '=', 1)
            ->where('amb_id', '=', $ambienteId)
            ->first();

        return $entries;
    }
}

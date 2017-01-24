<?php

namespace Modulos\Integracao\Listeners;

use Modulos\Integracao\Events\AtualizarSyncEvent;
use Modulos\Integracao\Repositories\SincronizacaoRepository;

class AtualizarSyncListener
{
    protected $sincronizacaoRepository;

    public function __construct(SincronizacaoRepository $sincronizacaoRepository)
    {
        $this->sincronizacaoRepository = $sincronizacaoRepository;
    }

    public function handle(AtualizarSyncEvent $event)
    {
        dd($event);
    }
}

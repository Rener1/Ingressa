<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\DeclaracaoPreMatricula;
use Illuminate\Support\Facades\Mail;

class EnviarDeclaracoesPreMatriculaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inscricoes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($inscricoes)
    {
        $this->inscricoes = $inscricoes;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->inscricoes as $inscricao) {
            Mail::to($inscricao->ds_email)->send(new DeclaracaoPreMatricula($inscricao));
        }
    }
}

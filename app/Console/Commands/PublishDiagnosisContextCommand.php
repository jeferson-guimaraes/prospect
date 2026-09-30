<?php

namespace App\Console\Commands;

use App\Services\DiagnosisContextService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishDiagnosisContextCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnostico:publicar-contexto
                            {--force : Sobrescreve o arquivo se ele já existir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria uma cópia editável do contexto da empresa usado no diagnóstico com IA';

    /**
     * Execute the console command.
     */
    public function handle(DiagnosisContextService $context): int
    {
        $target = $context->companyContextPath();

        if (File::isFile($target) && ! $this->option('force')) {
            $this->components->warn("O arquivo já existe: {$target}");
            $this->components->info('Use --force para sobrescrevê-lo com o exemplo.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($target));
        File::copy($context->companyContextExamplePath(), $target);

        $this->components->info("Contexto da empresa publicado em: {$target}");
        $this->components->info('Edite o arquivo para descrever a sua empresa; ele passa a ser usado no próximo diagnóstico.');

        return self::SUCCESS;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospeccao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('nome');
            $table->text('possiveis_dores')->nullable();
            $table->text('oportunidades_identificadas')->nullable();
            $table->text('perguntas_para_descoberta')->nullable();
            $table->text('sugestao_primeiro_contato')->nullable();
            $table->unsignedTinyInteger('lead_score')->nullable();
            $table->string('contato_responsavel')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('email')->nullable();
            $table->string('site')->nullable();
            $table->date('data_contato')->nullable();
            $table->enum('canal_contato', ['WhatsApp', 'Instagram', 'Email'])->nullable();
            $table->date('data_resposta')->nullable();
            $table->enum('retorno', [
                'Não Contatado',
                'Pendente',
                'Negativo',
                'Positivo',
                'Em Conversa',
                'Sem Oportunidade',
            ])->default('Não Contatado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospeccao');
    }
};

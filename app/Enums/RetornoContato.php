<?php

namespace App\Enums;

enum RetornoContato: string
{
    case NAO_CONTATADO = 'Não Contatado';
    case PENDENTE = 'Pendente';
    case NEGATIVO = 'Negativo';
    case POSITIVO = 'Positivo';
    case EM_CONVERSA = 'Em Conversa';
    case SEM_OPORTUNIDADE = 'Sem Oportunidade';
}

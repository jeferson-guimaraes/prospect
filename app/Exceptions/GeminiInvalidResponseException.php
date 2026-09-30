<?php

namespace App\Exceptions;

use Exception;

/**
 * Exceção lançada quando a API do Gemini retorna uma resposta vazia ou JSON inválido.
 */
class GeminiInvalidResponseException extends Exception {}

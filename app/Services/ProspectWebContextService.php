<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Coleta conteúdo público de site e Instagram para enriquecer o prompt de diagnóstico.
 */
class ProspectWebContextService
{
    private const int MAX_CONTENT_LENGTH = 4000;

    private const int FETCH_TIMEOUT = 15;

    private const int MAX_REDIRECTS = 3;

    /**
     * Busca e extrai conteúdo textual do site e do Instagram informados.
     *
     * Falhas de acesso são registradas em campos de erro sem interromper o fluxo,
     * permitindo que a análise prossiga com o contexto disponível.
     *
     * @return array{
     *     site_content: ?string,
     *     site_error: ?string,
     *     instagram_content: ?string,
     *     instagram_error: ?string
     * }
     */
    public function fetch(?string $site, ?string $instagram): array
    {
        $result = [
            'site_content' => null,
            'site_error' => null,
            'instagram_content' => null,
            'instagram_error' => null,
        ];

        if (! empty($site)) {
            [$content, $error] = $this->fetchUrl($this->normalizeSiteUrl($site));
            $result['site_content'] = $content;
            $result['site_error'] = $error;
        }

        if (! empty($instagram)) {
            [$content, $error] = $this->fetchUrl($this->normalizeInstagramUrl($instagram));
            $result['instagram_content'] = $content;
            $result['instagram_error'] = $error;
        }

        return $result;
    }

    /**
     * Normaliza a URL do site, adicionando o esquema HTTPS quando ausente.
     */
    private function normalizeSiteUrl(string $site): string
    {
        $site = trim($site);

        if (! preg_match('/^https?:\/\//i', $site)) {
            return 'https://'.ltrim($site, '/');
        }

        return $site;
    }

    /**
     * Normaliza o handle ou URL do Instagram para o formato de perfil público.
     */
    private function normalizeInstagramUrl(string $instagram): string
    {
        $instagram = trim($instagram);

        if (preg_match('/^https?:\/\//i', $instagram)) {
            return rtrim($instagram, '/').'/';
        }

        $handle = ltrim($instagram, '@');

        return "https://www.instagram.com/{$handle}/";
    }

    /**
     * Realiza o fetch HTTP de uma URL e retorna o conteúdo extraído ou mensagem de erro.
     *
     * Redirecionamentos são seguidos manualmente para que cada destino passe
     * pela mesma validação de URL.
     *
     * @return array{0: ?string, 1: ?string} Tupla com conteúdo extraído e mensagem de erro, respectivamente.
     */
    private function fetchUrl(string $url): array
    {
        try {
            for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
                if (! $this->isAllowedUrl($url)) {
                    return [null, "Endereço não permitido: {$url}."];
                }

                $response = $this->request($url);

                if (! $response->redirect()) {
                    break;
                }

                $location = $response->header('Location');

                if ($location === '') {
                    break;
                }

                $url = $this->resolveRedirect($url, $location);
            }

            if ($response->redirect()) {
                return [null, "Muitos redirecionamentos ao acessar {$url}."];
            }

            if (! $response->successful()) {
                return [null, "Não foi possível acessar {$url} (HTTP {$response->status()})."];
            }

            $content = $this->extractTextFromHtml($response->body());

            if ($content === '') {
                return [null, "Nenhum conteúdo útil extraído de {$url}."];
            }

            return [$content, null];
        } catch (Throwable $e) {
            return [null, "Erro ao acessar {$url}: {$e->getMessage()}"];
        }
    }

    private function request(string $url): Response
    {
        return Http::timeout(self::FETCH_TIMEOUT)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders([
                'User-Agent' => config('app.name').'-Diagnosis/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->get($url);
    }

    /**
     * Aceita apenas URLs HTTP(S) públicas, rejeitando endereços locais e de rede interna.
     */
    private function isAllowedUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower(trim($parts['host'], '[]'));

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    private function resolveRedirect(string $base, string $location): string
    {
        if (preg_match('/^https?:\/\//i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($location, '/')) {
            return $origin.$location;
        }

        $path = $parts['path'] ?? '/';
        $directory = substr($path, 0, strrpos($path, '/') + 1);

        return $origin.$directory.$location;
    }

    /**
     * Extrai título, metadados, headings e trecho de texto de um documento HTML.
     */
    private function extractTextFromHtml(string $html): string
    {
        $parts = [];

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $parts[] = 'Título: '.trim(html_entity_decode(strip_tags($matches[1])));
        }

        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/i', $html, $matches)) {
            $parts[] = 'Descrição: '.trim(html_entity_decode($matches[1]));
        }

        if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:description["\']/i', $html, $matches)) {
            $parts[] = 'OG Descrição: '.trim(html_entity_decode($matches[1]));
        }

        if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $headingMatches)) {
            $headings = array_map(
                fn (string $heading) => trim(html_entity_decode(strip_tags($heading))),
                $headingMatches[1]
            );
            $headings = array_filter($headings);
            if ($headings !== []) {
                $parts[] = 'Títulos: '.implode(' | ', array_slice($headings, 0, 10));
            }
        }

        $withoutScripts = preg_replace('/<(script|style|noscript)\b[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $bodyText = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($withoutScripts))) ?? '');
        if ($bodyText !== '') {
            $parts[] = 'Conteúdo: '.mb_substr($bodyText, 0, self::MAX_CONTENT_LENGTH);
        }

        $content = implode("\n", $parts);

        return mb_substr($content, 0, self::MAX_CONTENT_LENGTH);
    }
}

<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubRepositoryService
{
    private const MAX_REPOSITORIES = 12;

    /**
     * Fetch the user's real, public, non-fork repositories from the GitHub GraphQL API.
     *
     * @param  string  $login  GitHub username
     * @return array<int, array{name: string, description: ?string, url: string, stars: int, language: ?string, languageColor: ?string, updatedAt: string}>
     */
    public function getPublicRepositories(string $login): array
    {
        $cacheKey = "github-repositories:{$login}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($login) {
            return $this->fetch($login);
        });
    }

    private function fetch(string $login): array
    {
        $query = <<<'GRAPHQL'
            query ($login: String!, $count: Int!) {
                user(login: $login) {
                    repositories(
                        first: $count
                        privacy: PUBLIC
                        isFork: false
                        ownerAffiliations: OWNER
                        orderBy: { field: UPDATED_AT, direction: DESC }
                    ) {
                        nodes {
                            name
                            description
                            url
                            stargazerCount
                            updatedAt
                            primaryLanguage {
                                name
                                color
                            }
                        }
                    }
                }
            }
            GRAPHQL;

        $response = Http::withToken(config('services.github.token'))
            ->acceptJson()
            ->post('https://api.github.com/graphql', [
                'query' => $query,
                'variables' => [
                    'login' => $login,
                    'count' => self::MAX_REPOSITORIES,
                ],
            ]);

        if ($response->failed() || isset($response->json()['errors'])) {
            Log::warning('GitHub repositories request failed', [
                'login' => $login,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $nodes = $response->json('data.user.repositories.nodes') ?? [];

        return array_map(fn (array $repo) => [
            'name' => $repo['name'],
            'description' => $repo['description'],
            'url' => $repo['url'],
            'stars' => $repo['stargazerCount'],
            'language' => $repo['primaryLanguage']['name'] ?? null,
            'languageColor' => $repo['primaryLanguage']['color'] ?? null,
            'updatedAt' => $repo['updatedAt'],
        ], $nodes);
    }
}

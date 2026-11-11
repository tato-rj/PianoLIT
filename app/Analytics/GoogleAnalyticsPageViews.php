<?php

namespace App\Analytics;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use UnexpectedValueException;

class GoogleAnalyticsPageViews
{
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function configured(): bool
    {
        $property = (string) config('google-analytics.property_id');
        $credentials = config('google-analytics.report_credentials');
        return ctype_digit($property) && is_string($credentials) && is_file($credentials) && is_readable($credentials);
    }

    /** @return array<string, int> GA counts keyed by the requested page path. */
    public function fetch(string $host, array $paths, string $from, string $through): array
    {
        if (!$paths || $through < $from) return [];
        if (!$this->configured()) throw new UnexpectedValueException('Google Analytics reporting is not configured.');

        $property = (string) config('google-analytics.property_id');
        $credentials = config('google-analytics.report_credentials');
        $account = new ServiceAccountCredentials(self::SCOPE, $credentials);
        $token = $account->fetchAuthToken(fn (RequestInterface $request) => $this->http()
            ->withBody((string) $request->getBody(), 'application/x-www-form-urlencoded')
            ->post((string) $request->getUri())->throw()->toPsrResponse());
        if (empty($token['access_token']) || !is_string($token['access_token'])) {
            throw new UnexpectedValueException('Google did not return an access token.');
        }

        $counts = [];
        foreach (array_chunk(array_unique($paths), 100) as $batch) {
            $counts += $this->report($property, $token['access_token'], $host, $batch, $from, $through);
        }
        return $counts;
    }

    private function report(string $property, string $token, string $host, array $paths, string $from, string $through): array
    {
        $report = $this->http()->withToken($token)
            ->post('https://analyticsdata.googleapis.com/v1beta/properties/'.$property.':runReport', [
                'dateRanges' => [['startDate' => $from, 'endDate' => $through]],
                'dimensions' => [['name' => 'pagePath']],
                'metrics' => [['name' => 'screenPageViews']],
                'dimensionFilter' => ['andGroup' => ['expressions' => [
                    ['filter' => ['fieldName' => 'hostName', 'stringFilter' => [
                        'matchType' => 'EXACT', 'value' => $host,
                    ]]],
                    ['filter' => ['fieldName' => 'pagePath', 'inListFilter' => [
                        'values' => $paths, 'caseSensitive' => true,
                    ]]],
                ]]],
                'limit' => (string) count($paths),
            ])->throw()->json();

        $rows = $report['rows'] ?? [];
        if (($report['dimensionHeaders'][0]['name'] ?? null) !== 'pagePath'
            || ($report['metricHeaders'][0]['name'] ?? null) !== 'screenPageViews'
            || !is_array($rows) || ($report['rowCount'] ?? 0) !== count($rows)) {
            throw new UnexpectedValueException('Invalid Google Analytics page views report.');
        }

        $metadata = $report['metadata'] ?? [];
        if (!empty($metadata['samplingMetadatas']) || !empty($metadata['dataTruncationReasons'])) return [];

        $expected = array_fill_keys($paths, 0);
        $counts = [];
        foreach ($rows as $row) {
            $path = $row['dimensionValues'][0]['value'] ?? null;
            $value = $row['metricValues'][0]['value'] ?? null;
            $count = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if (!is_string($path) || !isset($expected[$path]) || isset($counts[$path]) || $count === false) {
                throw new UnexpectedValueException('Invalid Google Analytics page views row.');
            }
            $counts[$path] = $count;
        }

        // An omitted path means zero only when GA confirms a complete report.
        $incomplete = !empty($metadata['subjectToThresholding']) || !empty($metadata['dataLossFromOtherRow']);
        return $incomplete ? $counts : $counts + $expected;
    }

    private function http()
    {
        return Http::acceptJson()->timeout(15)->withOptions(['connect_timeout' => 3])->withoutRedirecting();
    }
}

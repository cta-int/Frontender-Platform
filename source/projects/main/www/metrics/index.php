<?php

require '../../vendor/autoload.php';
defined('ROOT_PATH') || define('ROOT_PATH', dirname(__DIR__, 2));

$config = new Frontender\Core\Config\Config();

$defaultValues = array_merge([
    'start' => '',
    'end' => date('Y-m-d', time() + 86400),
    'limit' => 100,
    'sortby' => 'timings.end',
    'sortdir' => '-1',
    'type' => 'csv'
], $_GET);
$sortProperties = [
    'timings.start' => 'Start time',
    'timings.end' => 'End time',
    'timings.duration' => 'Duration',
    'response.size' => 'Response size',
    'page' => 'Webpage'
];

function formatValue($value, $format)
{
    switch($format) {
        case 'date':
            return date('Y-m-d H:i:s', $value);
        default:
            return $value;
    }
}

function formatLine($log, $columns, $map = [])
{
    $line = [];

    foreach($columns as $column)
    {
        $value = array_reduce(explode('.', $column), function($carry, $key) {
            if(!$carry || !isset($carry->{$key})) {
                return false;
            }

            return $carry->{$key};
        }, $log);

        if(is_array($value) || is_object($value)) {
            $line[] = json_encode($value);
        } else {
            if(isset($map[$column])) {
                $value = formatValue($value, $map[$column]);
            }

            $line[] = $value;
        }
    }

    return $line;
}

if(!empty($_GET) && isset($_GET['type'])) {
    // Create csv and download.
    // Now we have to make the filter.
    $start = $defaultValues['start'] ? date('U', strtotime($defaultValues['start'])) : false;
    $end = $defaultValues['end'] ? date('U', strtotime($defaultValues['end'])) : false;
    $columns = [
        'user_session_id' => 'user.session_id',
        'request.uri' => 'request.uri',
        'page.route' => 'page.route',
        'timings.start' => 'timings.start',
        'timings.end' => 'timings.end',
        'response.size' => 'response.size (bytes)',
        'timings.duration' => 'duration (seconds)',
        'request.body' => 'request.body',
        'model.name' => 'model.name',
        'model.state' => 'model.state'
    ];
    $columnMap = [
        'timings.start' => 'date',
        'timings.end' => 'date'
    ];

    $filter = [];
    $csv = tmpfile();
    $csvDownloadName = $_SERVER['HTTP_HOST'] . '-' . date('Ymd') . '-' . date('Hi') . '-metrics.csv';

    if($start) {
        $filter['timings.start'] = ['$gte' => (float) $start];
    }

    if($end) {
        $filter['timings.end'] = ['$lte' => (float) $end];
    }

    $logs = Frontender\Core\DB\Adapter::getInstance()
        ->collection('log')
        ->find($filter, [
            'sort' => [
                $defaultValues['sortby'] => (int) $defaultValues['sortdir']
            ],
            'limit' => (int) $defaultValues['limit']
        ])
        ->toArray();

    fputcsv($csv, array_values($columns));

    foreach($logs as $log) {
        fputcsv($csv, formatLine($log, array_keys($columns), $columnMap));
    }

    fseek($csv, 0);
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $csvDownloadName . '";');
    fpassthru($csv);
    fclose($csv);

    return true;
}
?>

<h2>Metrics export</h2>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <label for="start">Start date</label>
    <input type="date" id="start" name="start">

    <br>

    <label for="end">End date</label>
    <input type="date" id="end" name="end" value="<?php echo $defaultValues['end']; ?>">

    <br>

    <label for="limit">Limit</label>
    <input type="number" id="limit" name="limit" value="<?php echo $defaultValues['limit']; ?>">

    <br>

    <label for="sortby">Sort property</label>
    <select name="sortby" id="sortby">
        <?php foreach($sortProperties as $property => $label) : ?>
            <option value="<?php echo $property; ?>"<?php echo $property === $defaultValues['sortby'] ? ' selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>

    <br>

    <label for="sortdir">Sort direction</label>
    <select name="sortdir" id="sortdir">
        <option value="1"<?php echo $defaultValues['sortdir'] === '1' ? 'selected' : ''; ?>>Ascending</option>
        <option value="-1"<?php echo $defaultValues['sortdir'] === '-1' ? 'selected' : ''; ?>>Descending</option>
    </select>

    <br>

    <input type="hidden" name="type" value="csv">
    <input type="submit" value="Download file">
</form>
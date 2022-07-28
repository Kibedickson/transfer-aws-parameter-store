<?php

use Symfony\Component\Process\Process;

include('vendor/autoload.php');

$aws_profile_from = '';
$aws_profile_to = '';
$path_from = '';
$path_to = '';
$region_from = '';
$region_to = '';

putParams($aws_profile_from, $aws_profile_to, $path_from, $path_to, $region_from, $region_to);

function getSymfonyProcess($command)
{
    $process = new Process($command);
    $process->run();

    return $process;
}

function getParamsByPath($profile, $path, $region)
{
    $command = array(
        'aws', 'ssm', 'get-parameters-by-path', '--profile', $profile, '--path', $path, '--region', $region
    );

    $process = getSymfonyProcess($command);

    $data = $process->getOutput();

    return json_decode($data);
}

function formatParams($data, $path)
{
    $params = [];

    foreach ($data->Parameters as $parameter) {
        $word = explode("/", $parameter->Name);
        $key = array_pop($word);

        $params[] = [
            'name' => $path . $key,
            'type' => $parameter->Type,
            'value' => $parameter->Value,
        ];
    }

    return $params;
}

function putParams($profile_from, $profile_to, $path_from, $path_to, $region_from, $region_to)
{
    $data = getParamsByPath($profile_from, $path_from, $region_from);

    $params = formatParams($data, $path_to);

    foreach ($params as $param) {
        $command = array(
            'aws', 'ssm', 'put-parameter', '--region', $region_to, '--profile', $profile_to, '--name', $param['name'], '--type', $param['type'], '--value', $param['value']
        );

        var_dump('Processing... ' . $param['name']);

        $process = getSymfonyProcess($command);

        $process->getOutput();
        $error = $process->getErrorOutput();

        if ($error) {
            var_dump($error);
            die();
        }
        var_dump("Processed: " . $param['name']);
    }
}
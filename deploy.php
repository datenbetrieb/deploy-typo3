<?php
/**
 * deployment description for TYPO3 CMS
 *
 *  - basend on rsync strategy
 *  - uses upstream recipes from deployer
 *
 *   !!!! WARNING !!!!
 *     composer dependencies must be resolved before running this script
 *     because we include deployer via composer dependency as well
 *   !!!! WARNING !!!!
 *
**/
include 'vendor/deployer/deployer/recipe/common.php';
include 'vendor/deployphp/recipes/recipes/rsync.php';

set('keep_releases', 5);

set('shared_dirs', [
    'web/fileadmin',
    'web/uploads',
    'web/typo3temp'
]);

set('shared_files', [
    'web/typo3conf/HostConfiguration.php',
]);

set('rsync', [
    'exclude' => [
        //'.git',
        '/.temp',
        '/.vagrant',
        '/deploy.php',
        '/bin',
        '/Vagrantfile',
        '/logs',
        '/node_modules',
        '/README.md',
        '/Scripts',
        '/Grunt',
        '/Vagrant'
    ],
    'exclude-file' => false,
    'include' => [
        '/web',
        '/vendor'
    ],
    'include-file' => false,
    'filter' => [],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'rz',
    'timeout' => 180,
    'options' => ['delete','links'],
]);

server('default', getenv('DEPLOYER_HOST'), getenv('DEPLOYER_PORT'))
    ->user(getenv('DEPLOYER_USER'))
    ->env('deploy_path', getenv('DEPLOYER_DESTINATION'))
    ->identityFile();

/*
server('yourserver', 'yourserver.domain.tld', '22')
    ->user(getenv('DEPLOYER_USER'))
    ->env('deploy_path', getenv('DEPLOYER_DESTINATION'))
    ->identityFile('~/.ssh/id_rsa.pub')
    ->forwardAgent();
 */
env('rsync_src', __DIR__);
env('rsync_dest','{{release_path}}');

set('typo3_console', "cd {{deploy_path}}/current/web/ && typo3conf/ext/typo3_console/Scripts/typo3cms");

task('cms:cache_flush', function() {
    $typo3_console = get('typo3_console');
    run("$typo3_console cache:flush -f");
})->desc('flush caches');

task('cms:database_updateschema', function() {
    $typo3_console = get('typo3_console');
    run("$typo3_console database:updateschema \"field.add,field.change,table.add\"");
})->desc('flush caches');

task('cms:cache_warmup', function() {
    $typo3_console = get('typo3_console');
    run("$typo3_console cache:warmup");
})->desc('flush caches');




#################################################
#
# deploy task
#
#################################################
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    //'deploy:vendors', as we pull deployer via composer we must run composer ourselves BEFORE running this script
    'rsync:warmup',
    'rsync',
    'deploy:shared',
    'deploy:symlink',
    'cms:cache_flush',
    'cms:database_updateschema',
    //'cms:cache_warmup', // cache_warmup was not working for me
    'cleanup',
])->desc('Deploy TYPO3 CMS project');
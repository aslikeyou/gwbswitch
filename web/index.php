<?

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

$app = new Application();

Dotenv::load(__DIR__.'/..');

define('GIT_EXEC', getenv('GIT_EXEC'));
define('PATH2PROJECT', getenv('PATH2PROJECT'));
define('EXEC_AFTER', getenv('EXEC_AFTER'));


$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->match('/', function(Application $app, Request $request) {
    // maybe better to use this
    //chdir(PATH2PROJECT);

    $exec_after_text = '';

    if($request->get('Checkout') !== null) {
        $newbranch = $request->get('Checkout[name]', null, true);
        exec(sprintf('cd %s && %s checkout %s',PATH2PROJECT, GIT_EXEC , $newbranch), $output, $checkout_status);
        if(strlen(EXEC_AFTER) > 0) {
            exec(EXEC_AFTER, $comandres, $comanstat);
            if($comanstat !== 0) {
                $app->abort(500, 'Can not execute after script.');
            }
            $exec_after_text = implode(PHP_EOL, $comandres);
        }

        if($checkout_status !== 0) {
            $app->abort(500, 'Git return not 0 status. Can not make checkout.');
        }
    }

    exec(sprintf('cd %s && %s branch', PATH2PROJECT, GIT_EXEC), $all_branches, $return_status);

    $active_branch = array_values(array_filter($all_branches, function($branch) {
        return trim($branch, '*') !== $branch;
    }))[0];

    if($return_status !== 0) {
        $app->abort(500, 'Git return not 0 status. Can not get list of branches.');
    }

    return $app['twig']->render('index.html.twig', [
        'active_branch' => $active_branch,
        'all_branches'  => $all_branches,
        'special_text'  => $exec_after_text
    ]);
});


$app->run();
<?php
namespace Germania\RedirectUnauthorized;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Aura\Session\SegmentInterface;

/**
 * This middlewares handles the storage of the Startpage
 * in case the request has been marked as "401 Unauthorized".
 * After successful Login, it redirects user to this very URL.
 *
 *
 * Before Route:
 *     If Response is "401 Unauthorized",
 *     Middleware stores the requested page URL in the session.
 *
 * After Route:
 *     If Response is "204 No Content",
 *     Middleware stores the requested page URL in the session.
 *
 *
 * The PSR-7 ServerRequest must provide a `session` attribute
 * holding a `Aura\Session\SegmentInterface` instance.
 */
class Middleware
{

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var SegmentInterface
     */
    public $session;


    /**
     * @var string
     */
    public $login_url;


    /**
     * Session key to original-requested page URL
     * @var string
     */
    public $requested_page_key = 'RequestedPageBeforeLogin';

    /**
     * HTTP Status Code for "Unauthorized". Usually 401.
     * @var string
     */
    public $auth_required_status_code  = 401;

    /**
     * HTTP Status Code for Responses after successful login. Usually 204.
     * @var string
     */
    public $authorized_status_code = 204;



    /**
     * @param SegmentInterface  $session    Aura.Session Segment
     * @param string            $login_url  Login page URL
     * @param LoggerInterface   $logger     Optional: PSR-3 Logger
     */
    public function __construct( SegmentInterface $session, $login_url, LoggerInterface $logger = null)
    {
        $this->session   = $session;
        $this->login_url = $login_url;
        $this->logger    = $logger ?: new NullLogger;
    }


    /**
     * @param  Psr\Http\Message\ServerRequestInterface  $request  PSR7 request
     * @param  Psr\Http\Message\ResponseInterface       $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        // Shortcuts
        $requested_page_key = $this->requested_page_key;
        $session            = $this->session;


        // --------------------------------------------
        // 1. Before router:
        //    If user must authorize first, store requested page URL in session.
        // --------------------------------------------

        $status = $response->getStatusCode();
        $this->logger->info("Before Route: Found status code", ['status' => $status ]);

        if ($status == $this->auth_required_status_code):
            // Store startpage if not done already
            if ($startpage = $session->get( $requested_page_key)):
                $this->logger->info("Before Route: Found startpage in session: " . ($startpage ?: "(none?!)"));
            else:
                $uri = (string) $request->getUri(); // no __toString here
                $this->logger->info("Before Route: Store startpage in session", ['url' => $uri ?: "(none?!)" ]);
                $session->set( $requested_page_key, $uri);
            endif;

            $this->logger->info("Before Route: Redirect user to login page", [ 'url' => $this->login_url ]);
            return $response->withHeader('Location', $this->login_url);
        else:
            $this->logger->debug("Before Route: noop");
        endif;




        // --------------------------------------------
        // 2. Call next middleware
        // --------------------------------------------
        $response = $next($request, $response);


        // --------------------------------------------
        // 3. After Route:
        // Check for "401 Unauthorized" or "204 No Content"
        // --------------------------------------------

        $status = $response->getStatusCode();
        $this->logger->info("After Route: Found status code", ['status' => $status ]);

        switch ($status):

            case $this->auth_required_status_code:
                $this->logger->info("After Route: Redirect user to login page", [ 'url' => $this->login_url ]);
                return $response->withHeader('Location', $this->login_url);
                break;

            case $this->authorized_status_code:
                $default_start_url = $request->getUri()->getBaseUrl();
                $start_url = $session->get( $requested_page_key, $default_start_url );

                // Avoid redirects to login page
                $start_url = ($start_url != $this->login_url) ? $start_url : $default_start_url;

                $session->set( $requested_page_key, null ); // Reset Session
                $this->logger->info("After Route: Redirect to startpage", [ 'url' => $start_url ]);
                return $response->withHeader('Location', $start_url);
                break;
            default:
                $this->logger->debug("After Route: noop");
                break;
        endswitch;


        // --------------------------------------------
        // Finish
        // --------------------------------------------
        return $response;
    }


}

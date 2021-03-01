<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/2/27
 */

namespace Refink\Http;

class Route
{
    const HTTP_POST = 'POST';
    const HTTP_GET = 'GET';

    private static $routes = [];
    private static $middlewareAlias = [];


    /**
     * add a http get method static route
     * @param string $uri eg: /user/login
     * @param callable $func
     * @param array $middleware
     */
    public static function get(string $uri, callable $func, array $middleware = [])
    {
        self::addRoute(self::HTTP_GET, $uri, $func, $middleware);
    }

    public static function post(string $uri, callable $func, array $middleware = [])
    {
        self::addRoute(self::HTTP_POST, $uri, $func, $middleware);
    }

    private static function addRoute($httpMethod, string $uri, callable $func, array $middleware)
    {
        if (!empty(self::$currentGroupInfo['uri_prefix'])) {
            $uri = '/' . trim(self::$currentGroupInfo['uri_prefix'], "/") . '/' . trim($uri, "/");
        }
        if (!empty($middleware)) {
            self::bindMiddleware($httpMethod, $uri, $middleware);
        }
        if (!empty(self::$currentGroupInfo['middleware'])) {
            self::bindMiddleware($httpMethod, $uri, self::$currentGroupInfo['middleware']);
        }

        self::$routes[$httpMethod][$uri]['func'] = $func;
    }

    private static $currentGroupInfo = [
        'middleware' => [],
        'uri_prefix' => ''
    ];


    /**
     * 给路由绑定中间件
     * @param $httpMethod
     * @param $uri
     * @param array $middleware
     */
    private static function bindMiddleware($httpMethod, $uri, array $middleware = [])
    {
        if (isset(self::$routes[$httpMethod][$uri])) {
            return;
        }
        //bind current group middleware
        if (!empty(self::$currentGroupInfo['middleware'])) {
            foreach (self::$currentGroupInfo['middleware'] as $m) {
                if (isset(self::$routes[$httpMethod][$uri]) && in_array($m, self::$routes[$httpMethod][$uri]['middleware'])) {
                    continue;
                }
                self::$routes[$httpMethod][$uri]['middleware'][] = $m;
            }
        }
        //bing single set middleware
        foreach ($middleware as $m) {
            if (isset(self::$routes[$httpMethod][$uri]) && in_array($m, self::$routes[$httpMethod][$uri]['middleware'])) {
                continue;
            }
            self::$routes[$httpMethod][$uri]['middleware'][] = $m;
        }
    }

    /**
     * set route by group, with the same middlewares
     * @param string $uriPrefix
     * @param array $middleware
     * @param callable $func
     */
    public static function group(string $uriPrefix, array $middleware, callable $func)
    {
        self::$currentGroupInfo['middleware'] = $middleware;
        self::$currentGroupInfo['uri_prefix'] = $uriPrefix;
        call_user_func($func);
        self::$currentGroupInfo['middleware'] = [];
        self::$currentGroupInfo['uri_prefix'] = '';
    }

    /**
     * set the middleware alias
     * @param $alias
     * @param string|array $middleware
     */
    public static function setMiddlewareAlias($alias, $middleware)
    {
        self::$middlewareAlias[$alias] = $middleware;
    }

    public static function getMiddlewareByAlias($alias)
    {
        return self::$middlewareAlias[$alias];
    }

    public static function getRoutes()
    {
        return self::$routes;
    }

    public static function getRouteInfo($httpMethod, $uri)
    {
        return self::$routes[$httpMethod][$uri] ?? null;
    }
}
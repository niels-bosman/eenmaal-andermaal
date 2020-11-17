<?php

namespace Core;

/**
 * Class Router
 * @package Core
 */
class Router
{
    /**
     * @var mixed
     */
    private $request;
    
    /**
     * @var
     */
    public static $base;
    
    /**
     * @var string[]
     */
    private $routes = [
        '/'            => [
            'view'  => 'home',
            'title' => 'Homepagina',
        ],
        ''             => [
            'view'  => 'home',
            'title' => 'Homepagina',
        ],
        '/inloggen'    => [
            'view'  => 'login',
            'title' => 'Inloggen',
        ],
        '/registreren' => [
            'view'  => 'register',
            'title' => 'Registreren',
        ],
        '404'          => [
            'view'  => '404',
            'title' => 'Pagina niet gevonden',
        ],
    ];
    
    /**
     * Router constructor.
     * @param $dir
     */
    public function __construct($dir)
    {
        self::$base    = $dir;
        $this->request = $_SERVER['REQUEST_URI'];
        
        $this->check();
    }
    
    /**
     * Check if route matches and render matching view.
     */
    private function check(): void
    {
        foreach ($this->routes as $key => $route) {
            if ($this->request === $key) {
                View::render(null, $route);
                
                return;
            }
        }
        
        View::render(null, $this->routes['404']);
    }
}

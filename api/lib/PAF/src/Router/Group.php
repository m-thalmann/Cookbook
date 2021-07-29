<?php

namespace PAF\Router;

/**
 * This class defines a group containing routes and other groups
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class Group {
    /**
     * @var string The group-path (base path)
     */
    private $group;

    /**
     * @var string|null The (preferably absolute) path to a file, that is only loaded,
     *                  when this group is matched. The file can access the group
     *                  by using the provided $group variable, and adding for example
     *                  routes to it
     */
    private $lazyPath;

    /**
     * @var string The variable name, that is used to store the group object,
     *             when loading a lazy group file
     */
    private $lazyVariable;

    /**
     * @var Group[] The groups contained by this group
     */
    private $groups = [];

    /**
     * @var Route[] The routes contained by this group
     */
    private $routes = [];

    /**
     * Creates a new group
     *
     * @param string $group The group-path
     * @param string|null $lazyPath The lazy path
     * @param string $lazyVariable The name of the variable, where the group can be accessed in the $lazyPath
     */
    public function __construct(
        $group = '',
        $lazyPath = null,
        $lazyVariable = 'group'
    ) {
        if (empty($lazyVariable)) {
            throw new \InvalidArgumentException('LazyVariable cant be empty');
        }

        $this->group = $group;
        $this->lazyPath = $lazyPath;
        $this->lazyVariable = $lazyVariable;
    }

    /**
     * Add a route, matching all request methods, to this group
     *
     * @param string $path The path for this route
     * @param callable[] $targets The targets for this route
     * @see Group::map()
     *
     * @return $this
     */
    public function all($path, ...$targets) {
        return $this->map('*', $path, ...$targets);
    }

    /**
     * Add a route, matching all get requests, to this group
     *
     * @param string $path The path for this route
     * @param callable[] $targets The targets for this route
     * @see Group::map()
     *
     * @return $this
     */
    public function get($path, ...$targets) {
        return $this->map('GET', $path, ...$targets);
    }

    /**
     * Add a route, matching all post requests, to this group
     *
     * @param string $path The path for this route
     * @param callable[] $targets The targets for this route
     * @see Group::map()
     *
     * @return $this
     */
    public function post($path, ...$targets) {
        return $this->map('POST', $path, ...$targets);
    }

    /**
     * Add a route, matching all put requests, to this group
     *
     * @param string $path The path for this route
     * @param callable[] $targets The targets for this route
     * @see Group::map()
     *
     * @return $this
     */
    public function put($path, ...$targets) {
        return $this->map('PUT', $path, ...$targets);
    }

    /**
     * Add a route, matching all delete requests, to this group
     *
     * @param string $path The path for this route
     * @param callable[] $targets The targets for this route
     * @see Group::map()
     *
     * @return $this
     */
    public function delete($path, ...$targets) {
        return $this->map('DELETE', $path, ...$targets);
    }

    /**
     * Add a route, matching all head requests, to this group
     *
     * @param string $path The path for this route
     * @param callable[] $targets The targets for this route
     * @see Group::map()
     *
     * @return $this
     */
    public function head($path, ...$targets) {
        return $this->map('HEAD', $path, ...$targets);
    }

    /**
     * Add a route, matching the $method, with the given path.
     *
     * @param string $method The http-request-method (in caps), or a '*' for any
     * @param string $path The path for this route
     * @see Route::setPath()
     * @param callable[] $targets The targets for this route
     * @see Route::setTargets()
     *
     * @return $this
     */
    public function map($method, $path, ...$targets) {
        $this->routes[] = new Route($method, $path, $targets);

        return $this;
    }

    /**
     * Adds a new group to this group, if it doesn't exist and returns it,
     * otherwise just returns the pre-existing one
     *
     * @param string $group The group path
     * @param string|null $lazyPath The lazy path
     * @param string $lazyVariable The name of the variable, where the group can be accessed in the $lazyPath
     *
     * @return Group The created or existing router group
     */
    public function group($group, $lazyPath = null, $lazyVariable = 'group') {
        if (!isset($this->groups[$group])) {
            $this->groups[$group] = new Group($group, $lazyPath, $lazyVariable);
        }

        return $this->groups[$group];
    }

    /**
     * Tries to resolve a given method and path with groups and routes of
     * this group. If a group is found, the result of the resolve function of that
     * group is returned, if a route is matched, a array containing the method, path
     * and matched route, is returned. If nothing was matched null is returned
     *
     * <pre>
     * [
     *  "method" => string,
     *  "path" => string,
     *  "route" => Route,
     * ]
     * </pre>
     *
     * @param string $method The http-request-method
     * @param string $path The path to be matched
     *
     * @return array|null The matching-info, if a match was found, otherwise null
     */
    public function resolve($method, $path) {
        if ($this->lazyPath !== null) {
            $lazyVariable = $this->lazyVariable;

            $$lazyVariable = $this;
            require_once $this->lazyPath;
        }

        // prefer routes over groups

        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                return [
                    "method" => $method,
                    "path" => $path,
                    "route" => $route,
                ];
            }
        }

        foreach ($this->groups as $group => $obj) {
            if (substr($path, 0, strlen($group)) == $group) {
                return $obj->resolve($method, substr($path, strlen($group)));
            }
        }

        // no match
        return null;
    }
}

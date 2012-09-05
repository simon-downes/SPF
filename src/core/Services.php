<?php
/*
 * Dependency Injection Container, based on Pimple.
 *
 * Copyright (c) 2009 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Main services class.
 *
 * @package spf
 * @author  Fabien Potencier, Simon Downes
 */
class Services implements \ArrayAccess
{
    private $values = array();

    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same a name as an existing parameter would break your container).
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to defined an object
     */
    function offsetSet($id, $value)
    {
        $this->values[$id] = $value;
    }

    /**
     * Gets a parameter or an object.
     *
     * @param  string $id The unique identifier for the parameter or object
     *
     * @return mixed  The value of the parameter or an object
     *
     * @throws InvalidArgumentException if the identifier is not defined
     */
    function offsetGet($id)
    {
        
        if( !array_key_exists($id, $this->values) ) {
            
            $config = null;
            
            if( isset($this->values['config']) ) {
				// if using a shortcut to a logger that isn't defined then check for a definition in config and create it
				if( substr($id, 0, 4) == 'log.' ) {
					$config = $this['config']->get('logs.'. substr($id, 4));
					if( $config ) {
						$this[$id] = $this->share(function( $services ) use ($config) {
							return $services['logs']->create($config);
						});
					}
				}
				// if using a shortcut to a database that isn't defined then check for a definition in config and create it
				elseif( substr($id, 0, 3) == 'db.' ) {
					$config = $this['config']->get('databases.'. substr($id, 3));
					if( $config ) {
						$this[$id] = $this->share(function( $services ) use ($config) {
							return $services->database($config);
						});
					}
				}
			}
            
            if( !$config )
            	throw new Exception("Service '{$id}' is not defined"));
            	
        }

        return $this->values[$id] instanceof Closure ? $this->values[$id]($this) : $this->values[$id];
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @param  string $id The unique identifier for the parameter or object
     *
     * @return Boolean
     */
    function offsetExists($id)
    {
        
        if isset($this->values[$id])
        	return true;
        
        $exists = false;
        if( isset($this->values['config']) ) {
			$exists = (substr($id, 0, 4) == 'log.') && $this['config']->get('logs.'. substr($id, 4))
			$exists = $exists || ((substr($id, 0, 3) == 'db.') && $this['config']->get('databases.'. substr($id, 3)));
		}
		
    	return $exists;
    	
    }

    /**
     * Unsets a parameter or an object.
     *
     * @param  string $id The unique identifier for the parameter or object
     */
    function offsetUnset($id)
    {
        unset($this->values[$id]);
    }

    /**
     * Returns a closure that stores the result of the given closure for
     * uniqueness in the scope of this instance of Pimple.
     *
     * @param Closure $callable A closure to wrap for uniqueness
     *
     * @return Closure The wrapped closure
     */
    function share(Closure $callable)
    {
        return function ($c) use ($callable) {
            static $object;

            if (is_null($object)) {
                $object = $callable($c);
            }

            return $object;
        };
    }

    /**
     * Protects a callable from being interpreted as a service.
     *
     * This is useful when you want to store a callable as a parameter.
     *
     * @param Closure $callable A closure to protect from being evaluated
     *
     * @return Closure The protected closure
     */
    function protect(Closure $callable)
    {
        return function ($c) use ($callable) {
            return $callable;
        };
    }

    /**
     * Gets a parameter or the closure defining an object.
     *
     * @param  string $id The unique identifier for the parameter or object
     *
     * @return mixed  The value of the parameter or the closure defining an object
     *
     * @throws InvalidArgumentException if the identifier is not defined
     */
    function raw($id)
    {
        if (!array_key_exists($id, $this->values)) {
            throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->values[$id];
    }
}

// EOF

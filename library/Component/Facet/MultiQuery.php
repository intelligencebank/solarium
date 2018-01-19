<?php

namespace Solarium\Component\Facet;

use Solarium\Component\FacetSet;
use Solarium\Component\Facet\Query as FacetQuery;
use Solarium\Exception\InvalidArgumentException;

/**
 * Facet MultiQuery.
 *
 * This is a 'virtual' querytype that combines multiple facet queries into a
 * single resultset
 */
class MultiQuery extends AbstractFacet
{
    /**
     * Facet query objects.
     *
     * @var FacetQuery[]
     */
    protected $facetQueries = array();

    /**
     * Get the facet type.
     *
     * @return string
     */
    public function getType()
    {
        return FacetSet::FACET_MULTIQUERY;
    }

    /**
     * Create a new facetQuery.
     *
     * Convenience method so you don't need to manually create facetquery
     * objects.
     *
     * @param string $key
     * @param string $query
     * @param array  $excludes
     *
     * @return self Provides fluent interface
     */
    public function createQuery($key, $query, $excludes = array())
    {
        // merge excludes with shared excludes
        $excludes = array_merge($this->getExcludes(), $excludes);

        $facetQuery = new Query();
        $facetQuery->setKey($key);
        $facetQuery->setQuery($query);
        $facetQuery->setExcludes($excludes);

        return $this->addQuery($facetQuery);
    }

    /**
     * Add a facetquery.
     *
     * Supports a facetquery instance or a config array, in that case a new
     * facetquery instance wil be created based on the options.
     *
     * @throws InvalidArgumentException
     *
     * @param Query|array $facetQuery
     *
     * @return self Provides fluent interface
     */
    public function addQuery($facetQuery)
    {
        if (is_array($facetQuery)) {
            $facetQuery = new Query($facetQuery);
        }

        $key = $facetQuery->getKey();

        if (0 === strlen($key)) {
            throw new InvalidArgumentException('A facetquery must have a key value');
        }

        if (array_key_exists($key, $this->facetQueries)) {
            throw new InvalidArgumentException('A query must have a unique key value within a multiquery facet');
        }

        // forward shared excludes
        $facetQuery->addExcludes($this->getExcludes());

        $this->facetQueries[$key] = $facetQuery;

        return $this;
    }

    /**
     * Add multiple facetqueries.
     *
     * @param array $facetQueries Instances or config array
     *
     * @return self Provides fluent interface
     */
    public function addQueries(array $facetQueries)
    {
        foreach ($facetQueries as $key => $facetQuery) {
            // in case of a config array: add key to config
            if (is_array($facetQuery) && !isset($facetQuery['key'])) {
                $facetQuery['key'] = $key;
            }

            $this->addQuery($facetQuery);
        }

        return $this;
    }

    /**
     * Get a facetquery.
     *
     * @param string $key
     *
     * @return string
     */
    public function getQuery($key)
    {
        if (isset($this->facetQueries[$key])) {
            return $this->facetQueries[$key];
        } else {
            return;
        }
    }

    /**
     * Get all facetqueries.
     *
     * @return Query[]
     */
    public function getQueries()
    {
        return $this->facetQueries;
    }

    /**
     * Remove a single facetquery.
     *
     * You can remove a facetquery by passing its key or the facetquery instance.
     *
     * @param string|Query $query
     *
     * @return self Provides fluent interface
     */
    public function removeQuery($query)
    {
        if (is_object($query)) {
            $query = $query->getKey();
        }

        if (isset($this->facetQueries[$query])) {
            unset($this->facetQueries[$query]);
        }

        return $this;
    }

    /**
     * Remove all facetqueries.
     *
     * @return self Provides fluent interface
     */
    public function clearQueries()
    {
        $this->facetQueries = array();

        return $this;
    }

    /**
     * Set multiple facetqueries.
     *
     * This overwrites any existing facetqueries
     *
     * @param array $facetQueries
     *
     * @return self Provides fluent interface
     */
    public function setQueries($facetQueries)
    {
        $this->clearQueries();

        return $this->addQueries($facetQueries);
    }

    /**
     * Add an exclude tag.
     *
     * Excludes added to the MultiQuery facet a shared by all underlying
     * FacetQueries, so they must be forwarded to any existing instances.
     *
     * If you don't want to share an exclude use the addExclude method of a
     * specific FacetQuery instance instead.
     *
     * @param string $tag
     *
     * @return self Provides fluent interface
     */
    public function addExclude($tag)
    {
        foreach ($this->facetQueries as $facetQuery) {
            $facetQuery->addExclude($tag);
        }

        return parent::addExclude($tag);
    }

    /**
     * Remove a single exclude tag.
     *
     * Excludes added to the MultiQuery facet a shared by all underlying
     * FacetQueries, so changes must be forwarded to any existing instances.
     *
     * If you don't want this use the removeExclude method of a
     * specific FacetQuery instance instead.
     *
     * @param string $exclude
     *
     * @return self Provides fluent interface
     */
    public function removeExclude($exclude)
    {
        foreach ($this->facetQueries as $facetQuery) {
            $facetQuery->removeExclude($exclude);
        }

        return parent::removeExclude($exclude);
    }

    /**
     * Remove all excludes.
     *
     * Excludes added to the MultiQuery facet a shared by all underlying
     * FacetQueries, so changes must be forwarded to any existing instances.
     *
     * If you don't want this use the clearExcludes method of a
     * specific FacetQuery instance instead.
     *
     * @return self Provides fluent interface
     */
    public function clearExcludes()
    {
        foreach ($this->facetQueries as $facetQuery) {
            $facetQuery->clearExcludes();
        }

        return parent::clearExcludes();
    }

    /**
     * Initialize options.
     *
     * Several options need some extra checks or setup work, for these options
     * the setters are called.
     */
    protected function init()
    {
        parent::init();

        foreach ($this->options as $name => $value) {
            switch ($name) {
                case 'query':
                    if (!is_array($value)) {
                        $value = array(array('query' => $value));
                    }
                    $this->addQueries($value);
                    break;
            }
        }
    }
}
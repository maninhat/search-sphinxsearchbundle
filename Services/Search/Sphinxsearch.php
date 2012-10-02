<?php

namespace Search\SphinxsearchBundle\Services\Search;

use Search\SphinxsearchBundle\Services\Exception\ConnectionException;
use Doctrine\ORM\EntityManager;
class Sphinxsearch
{
	/**
	 * @var string $host
	 */
	private $host;

	/**
	 * @var string $port
	 */
	private $port;

	/**
	 * @var string $socket
	 */
	private $socket;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;


    /**
     * @var array
     */
    private $mapping;
	/**
	 * @var array $indexes
	 *
	 * $this->indexes should have the format:
	 *
	 *	$this->indexes = array(
	 *		'IndexLabel' => array(
	 *			'index_name'	=> 'IndexName',
	 *			'field_weights'	=> array(
	 *				'FieldName'	=> (int)'FieldWeight',
	 *				...,
	 *			),
	 *		),
	 *		...,
	 *	);
	 */
	private $indexes;

	/**
	 * @var SphinxClient $sphinx
	 */
	private $sphinx;

    /**
     * Constructor.
     *
     * @param string $host The server's host name/IP.
     * @param string $port The port that the server is listening on.
     * @param string $socket The UNIX socket that the server is listening on.
     * @param array $indexes The list of indexes that can be used.
     * @param array $mapping The list of mapping
     * @param float $timeout Timeout in seconds.
     * @param \Doctrine\ORM\EntityManager $em  for db query
     */
	public function __construct($host = 'localhost', $port = '9312', $socket = null, array $indexes = array(),array $mapping = array(), EntityManager $em = null, $timeout = 5)
	{
		$this->host = $host;
		$this->port = $port;
		$this->socket = $socket;
		$this->indexes = $indexes;
        $this->em=$em;
        $this->mapping=$mapping;


		$this->sphinx = new \SphinxClient();
		if( $this->socket !== null )
			$this->sphinx->setServer($this->socket);
		else
			$this->sphinx->setServer($this->host, $this->port);

        $this->sphinx->setConnectTimeout((float) $timeout);
	}

	/**
     * Set the desired match mode.
     *
	 * @param int $mode The matching mode to be used.
	 */
	public function setMatchMode($mode)
	{
		$this->sphinx->setMatchMode($mode);
	}

	public function setSortMode($mode, $str = '')
	{
		$this->sphinx->setSortMode($mode, $str);
	}

	/**
     * Set the desired search filter.
     *
	 * @param string $attribute The attribute to filter.
	 * @param array $values The values to filter.
	 * @param bool $exclude Is this an exclusion filter?
	 */
	public function setFilter($attribute, $values, $exclude = false)
	{
		$this->sphinx->setFilter($attribute, $values, $exclude);
	}

    /**
     * Set a filter range.
     *
     * @param string  $attribute The attribute to filter.
     * @param integer $min       Minimum value.
     * @param integer $max       Maxmimum value.
     * @param boolean $exclude   If set to <code>true</code>, matching
     *   documents are excluded from the result set.
     *
     * @return Returns <code>true</code> on success or <code>false</code> on
     *   failure.
     *
     * @see \SphinxClient::setFilterRange
     */
    public function setFilterRange($attribute, $min, $max, $exclude = false)
    {
        $this->sphinx->setFilterRange($attribute, (int) $min, (int) $max, (bool) $exclude);
    }

	/**
     * Search for the specified query string.
     *
	 * @param string $query The query string that we are searching for.
	 * @param array $indexes The indexes to perform the search on.
	 *
	 * @return ResultCollection The results of the search.
	 *
	 * $indexes should have the format:
	 *
	 *	$indexes = array(
	 *		'IndexLabel' => array(
	 *			'result_offset'	=> (int),
	 *			'result_limit'	=> (int)
	 *		),
	 *		...,
	 *	);
	 */
	public function search($query, array $indexes)
	{
		// $query = $this->sphinx->escapeString($query);

		$results = array();
		$fieldWeights = array();
		$options = array(
            'result_offset' => 0,
            'result_limit' => 1000,
            'max_matches' => 1000,
        );

        // Combine the options of each index
        foreach ($this->indexes as $label => $info) {
            if (!isset($this->indexes[$label])) {
                continue;
            }

            if (!empty($info['field_weights'])) {
                $fieldWeights = array_merge($fieldWeights, $info['field_weights']);
            }
        }

        if (!empty($fieldWeights)) {
            $this->sphinx->setFieldWeights($fieldWeights);
        }

        foreach ($options as $key => $value) {
            foreach ($this->indexes as $label => $info) {
                if (isset($info[$key])) {
                    $options[$key] = (int) $info[$key];
                }
            }
        }

        // There are assert() calls in the PHP version so always cast these
        $this->sphinx->setLimits((int) $options['result_offset'], (int) $options['result_limit'], $options['max_matches']);

        foreach ($indexes as $label => $options) {
         if( !isset($this->indexes[$label]) ) {
                continue;
            }
            $this->sphinx->addQuery($query, $this->indexes[$label]['index_name']);
        }

        $allResults = $this->sphinx->runQueries();
        $lastError = $this->sphinx->getLastError();

        if (is_callable(array($this->sphinx, 'IsConnectError')) && $this->sphinx->IsConnectError()) { // PHP version
            throw new ConnectionException(sprintf('Searching for "%s" failed with error "%s".', $query, $lastError ? $lastError : ucfirst($allResults[0]['error'])));
        }
        else if ($allResults[0]['status'] !== SEARCHD_OK) {
            throw new \RuntimeException(sprintf('Searching for "%s" failed with error "%s".', $query, $lastError ? $lastError : ucfirst($allResults[0]['error'])));
        }

        foreach ($allResults as $resultSet) {
            if (!isset($resultSet['matches'])) {
                continue;
            }

            $label = null;

            foreach ($resultSet['matches'] as $uniqueId => $data) {
                if ($label === null) {
                    $label = $data['attrs']['index_name'];
                    $results[$label] = $resultSet;
                    break;
                }
            }
        }

		/**
		 * FIXME: Throw an exception if $results is empty?
		 */
        return new ResultCollection($results,$this->mapping,$this->em);
	}

  public function escapeString($string)
  {
    return $this->sphinx->escapeString($string);
  }
}

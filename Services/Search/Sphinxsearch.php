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
     * @param \Doctrine\ORM\EntityManager $em  for db query
     */
	public function __construct($host = 'localhost', $port = '9312', $socket = null, array $indexes = array(),array $mapping = array(), EntityManager $em = null)
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
		foreach( $indexes as $label => $options ) {
			/**
			 * Ensure that the label corresponds to a defined index.
			 */
			if( !isset($this->indexes[$label]) )
				continue;

			/**
			 * Set the offset and limit for the returned results.
			 */

            // On-going bug even in 1.2.0
            // http://sphinxsearch.com/bugs/view.php?id=208
            $maxMatches = $defaultMaxMatches = 1000;
            if (isset($options['max_matches'])) {
                $maxMatches = (int) $options['max_matches'];
                if ($maxMatches === 0) {
                    $maxMatches = $defaultMaxMatches;
                }
            }

			if( isset($options['result_offset']) && isset($options['result_limit']) )
				$this->sphinx->setLimits($options['result_offset'], $options['result_limit'], $maxMatches);

			/**
			 * Weight the individual fields.
			 */
			if( !empty($this->indexes[$label]['field_weights']) )
				$this->sphinx->setFieldWeights($this->indexes[$label]['field_weights']);

			/**
			 * Perform the query.
			 */
			$results[$label] = $this->sphinx->query($query, $this->indexes[$label]['index_name']);

            if (is_callable(array($this->sphinx, 'IsConnectError')) && $this->sphinx->IsConnectError()) { // PHP version
				throw new ConnectionException(sprintf('Searching index "%s" for "%s" failed with error "%s".', $label, $query, $this->sphinx->getLastError()));
            } elseif($results[$label]['status'] !== SEARCHD_OK) {
				throw new \RuntimeException(sprintf('Searching index "%s" for "%s" failed with error "%s".', $label, $query, $this->sphinx->getLastError()));
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

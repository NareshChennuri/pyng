<?php

class JO_Dom_Query {
	/**#@+
	 * Document types
	 */
	const DOC_XML   = 'docXml';
	const DOC_HTML  = 'docHtml';
	const DOC_XHTML = 'docXhtml';
	/**#@-*/
	
	/**
	 * @var string
	 */
	protected $_document;
	
	/**
	 * DOMDocument errors, if any
	 * @var false|array
	 */
	protected $_documentErrors = false;
	
	/**
	 * Document type
	 * @var string
	 */
	protected $_docType;
	
	/**
	 * Document encoding
	 * @var null|string
	 */
	protected $_encoding;
	
	/**
	 * XPath namespaces
	 * @var array
	 */
	protected $_xpathNamespaces = array();
	
	/**
	 * Constructor
	 *
	 * @param  null|string $document
	 * @return void
	 */
	public function __construct($document = null, $encoding = null)
	{
		$this->setEncoding($encoding);
		$this->setDocument($document);
	}
	
	/**
	 * Set document encoding
	 *
	 * @param  string $encoding
	 * @return JO_Dom_Query
	 */
	public function setEncoding($encoding)
	{
		$this->_encoding = (null === $encoding) ? null : (string) $encoding;
		return $this;
	}
	
	/**
	 * Get document encoding
	 *
	 * @return null|string
	 */
	public function getEncoding()
	{
		return $this->_encoding;
	}
	
	/**
	 * Set document to query
	 *
	 * @param  string $document
	 * @param  null|string $encoding Document encoding
	 * @return JO_Dom_Query
	 */
	public function setDocument($document, $encoding = null)
	{
		if (0 === strlen($document)) {
			return $this;
		}
		if (strstr($document, 'DTD XHTML')) {
			return $this->setDocumentXhtml($document, $encoding);
		}
		// breaking XML declaration to make syntax highlighting work
		if ('<' . '?xml' == substr(trim($document), 0, 5)) {
			return $this->setDocumentXml($document, $encoding);
		}
		return $this->setDocumentHtml($document, $encoding);
	}
	
	/**
	 * Register HTML document
	 *
	 * @param  string $document
	 * @param  null|string $encoding Document encoding
	 * @return JO_Dom_Query
	 */
	public function setDocumentHtml($document, $encoding = null)
	{
		$this->_document = (string) $document;
		$this->_docType  = self::DOC_HTML;
		if (null !== $encoding) {
			$this->setEncoding($encoding);
		}
		return $this;
	}
	
	/**
	 * Register XHTML document
	 *
	 * @param  string $document
	 * @param  null|string $encoding Document encoding
	 * @return JO_Dom_Query
	 */
	public function setDocumentXhtml($document, $encoding = null)
	{
		$this->_document = (string) $document;
		$this->_docType  = self::DOC_XHTML;
		if (null !== $encoding) {
			$this->setEncoding($encoding);
		}
		return $this;
	}
	
	/**
	 * Register XML document
	 *
	 * @param  string $document
	 * @param  null|string $encoding Document encoding
	 * @return JO_Dom_Query
	 */
	public function setDocumentXml($document, $encoding = null)
	{
		$this->_document = (string) $document;
		$this->_docType  = self::DOC_XML;
		if (null !== $encoding) {
			$this->setEncoding($encoding);
		}
		return $this;
	}
	
	/**
	 * Retrieve current document
	 *
	 * @return string
	 */
	public function getDocument()
	{
		return $this->_document;
	}
	
	/**
	 * Get document type
	 *
	 * @return string
	 */
	public function getDocumentType()
	{
		return $this->_docType;
	}
	
	/**
	 * Get any DOMDocument errors found
	 *
	 * @return false|array
	 */
	public function getDocumentErrors()
	{
		return $this->_documentErrors;
	}
	
	/**
	 * Perform a CSS selector query
	 *
	 * @param  string $query
	 * @return JO_Dom_Query_Result
	 */
	public function query($query)
	{
		$xpathQuery = JO_Dom_Query_Css2Xpath::transform($query);
		return $this->queryXpath($xpathQuery, $query);
	}
	
	/**
	 * get document meta tags and title
	 * @return multitype:string 
	 */
	public function getMetatags() {
		$metas = array();
		$title = $this->query('title');
		if($title->count()) {
			$metas['title'] = $title->innerHtml();
		}
		$query = $this->query('meta');
		if($query->count()) {
			$nodes = $query->getNodeList();
			foreach($nodes AS $meta) {
				if($meta->attributes) {
					$key = $value = '';
					foreach($meta->attributes AS $attr) {
						switch( mb_strtolower($attr->name, 'utf-8') ) {
							case 'http-equiv':
							case 'name':
							case 'property':
								$key = mb_strtolower($attr->value, 'utf-8');
							break;
							case 'content':
								$value = mb_strtolower($attr->value, 'utf-8');
							break;
						}
					}
					if($key && $value) {
						$metas[($key == 'title' ? 'meta-title' : $key)] = $value;
					}
				}
			}
		}
		return $metas;
	}
	
	/**
	 * Perform an XPath query
	 *
	 * @param  string|array $xpathQuery
	 * @param  string $query CSS selector query
	 * @return JO_Dom_Query_Result
	 */
	public function queryXpath($xpathQuery, $query = null)
	{
		if (null === ($document = $this->getDocument())) {
			throw new JO_Dom_Exception('Cannot query; no document registered');
		}
	
		$encoding = $this->getEncoding();
		libxml_use_internal_errors(true);
		if (null === $encoding) {
			$domDoc = new DOMDocument('1.0');
		} else {
			$domDoc = new DOMDocument('1.0', $encoding);
		}
		$type   = $this->getDocumentType();
		switch ($type) {
			case self::DOC_XML:
				$success = $domDoc->loadXML($document);
				if(!$success) {
					$success = $domDoc->loadHTML($document);
				}
				break;
			case self::DOC_HTML:
			case self::DOC_XHTML:
			default:
				$success = $domDoc->loadHTML($document);
				break;
		}
		$errors = libxml_get_errors();
		if (!empty($errors)) {
			$this->_documentErrors = $errors;
			libxml_clear_errors();
		}
		libxml_use_internal_errors(false);
	
		if (!$success) {
			throw new JO_Dom_Exception(sprintf('Error parsing document (type == %s)', $type));
		}
	
		$nodeList   = $this->_getNodeList($domDoc, $xpathQuery);
		return new JO_Dom_Query_Result($query, $xpathQuery, $domDoc, $nodeList);
	}
	
	/**
	 * Register XPath namespaces
	 *
	 * @param   array $xpathNamespaces
	 * @return  void
	 */
	public function registerXpathNamespaces($xpathNamespaces)
	{
		$this->_xpathNamespaces = $xpathNamespaces;
	}
	
	/**
	 * Prepare node list
	 *
	 * @param  DOMDocument $document
	 * @param  string|array $xpathQuery
	 * @return array
	 */
	protected function _getNodeList($document, $xpathQuery)
	{
		$xpath      = new DOMXPath($document);
		foreach ($this->_xpathNamespaces as $prefix => $namespaceUri) {
			$xpath->registerNamespace($prefix, $namespaceUri);
		}
		$xpathQuery = (string) $xpathQuery;
		if (preg_match_all('|\[contains\((@[a-z0-9_-]+),\s?\' |i', $xpathQuery, $matches)) {
			foreach ($matches[1] as $attribute) {
				$queryString = '//*[' . $attribute . ']';
				$attributeName = substr($attribute, 1);
				$nodes = $xpath->query($queryString);
				foreach ($nodes as $node) {
					$attr = $node->attributes->getNamedItem($attributeName);
					$attr->value = ' ' . $attr->value . ' ';
				}
			}
		}
		return $xpath->query($xpathQuery);
	}
}

?>
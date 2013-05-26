<?php

namespace Narvalo\Web;

require_once 'NarvaloBundle.php';

use \Narvalo;

// Core classes
// =================================================================================================

const HTTP_EOL = "\n";

// {{{ HttpException

class HttpException extends Narvalo\Exception { }

// }}} ---------------------------------------------------------------------------------------------

// {{{ HttpVersion

final class HttpVersion {
  const
    V_1_0 = '1.0',
    V_1_1 = '1.1';
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpVerb

final class HttpVerb {
  const
    Get    = 'GET',
    Post   = 'POST',
    Put    = 'PUT',
    Delete = 'DELETE',
    Head   = 'HEAD';
}

// }}} ---------------------------------------------------------------------------------------------

// {{{ HttpHeaders

class HttpHeaders implements \Iterator {
  private $_headers = array();

  function __toString() {
    $str = '';

    foreach ($this->_headers as $k => $v) {
      $str .= "$k: $v" . HTTP_EOL;
    }

    return $str;
  }

  //  function toArray() {
  //    return $this->_headers;
  //  }

  function getHeader($_name_) {
    return isset($this->_headers[$_name_]) ? $this->_headers[$_name_] : \NULL;
  }

  function setHeader($_name_, $_value_) {
    $this->_headers[$_name_] = $_value_;
  }

  //  function initHeader($_name_, $_value_) {
  //    if (!\array_key_exists($_name_, $this->_headers)) {
  //      $this->_headers[$_name_] = $_value_;
  //    }
  //  }

  function unsetHeader($_name_) {
    unset($this->_headers[$_name_]);
  }

  function reset() {
    $this->_headers = array();
  }

  //  function setDate() { }
  //  function setUserAgent() { }
  //  function setContentType() { }
  //  function setFrom() { }
  //  function setAuthorizationBasic() { }
  //  function setProxyAuthorizationBasic() { }
  //  function setExpires() { }
  //  function setIfModifiedSince() { }
  //  function setIfUnmodifiedSince() { }
  //  function setLastModified() { }
  //  function setContentEncoding() { }
  //  function setContentLength() { }
  //  function setContentLanguage() { }
  //  function setTitle() { }
  //  function setServer() { }
  //  function setReferer() { }
  //  function setWwwAuthenticate() { }
  //  function setAuthorization() { }
  //  function setProxyAuthorization() { }

  function rewind() {
    rewind($this->_headers);
  }

  function current() {
    return current($this->_headers);
  }

  function key() {
    return key($this->_headers);
  }

  function next() {
    return next($this->_headers);
  }

  function valid() {
    return valid($this->_headers);
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpRequest

class HttpRequest {
  private
    $_protocol,
    $_method,
    $_url,
    $_headers,
    $_content;

  function __construct($_protocol_, $_method_, Url $_url_, HttpHeaders $_headers_) {
    $this->_protocol = $_protocol_;
    $this->_method   = $_method_;
    $this->_url      = $_url_;
    $this->_headers  = $_headers_;
    $this->_content  = '';
  }

  function setContent($_content_) {
    $this->_content = $_content_;
  }

  function getProtocol() {
    return $this->_protocol;
  }

  function getMethod() {
    return $this->_method;
  }

  function getHeaders() {
    return $this->_headers;
  }

  function getUrl() {
    return $this->_url;
  }

  function getContent() {
    return $this->_content;
  }

  function __toString() {
    return $this->_method . ' ' . $this->_url . HTTP_EOL
      . $this->_headers . HTTP_EOL
      . $this->_content;
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ HttpResponse

class HttpResponse {
  const
    Pending   = 0,
    Info      = 1,
    Ok        = 2,
    More      = 3,
    Reject    = 4,
    Error     = 5;

  protected
    $_protocol,
    $_status,
    $_message,
    $_headers,
    $_body;
  //$statusCode,
  //$statusDescription,
  //$subStatusCode;

  function __construct($_protocol_, $_status_, $_message_, HttpHeaders $_headers_, $_body_) {
    $this->_protocol = $_protocol_;
    $this->_status   = $_status_;
    $this->_message  = $_message_;
    $this->_headers  = $_headers_;
    $this->_body     = $_body_;
  }

  function getProtocol() {
    return $this->_protocol;
  }

  function getStatus() {
    return $this->_status;
  }

  function getMessage() {
    return $this->_message;
  }

  function getHeaders() {
    return $this->_headers;
  }

  function getBody() {
    return $this->_body;
  }

  function isPending() {
    return self::Pending == $this->_status[0];
  }

  function isInformational() {
    return self::Info == $this->_status[0];
  }

  function isSuccessful() {
    return self::Ok == $this->_status[0];
  }

  function isRedirection() {
    return self::More == $this->_status[0];
  }

  function isClientError() {
    return self::Reject == $this->_status[0];
  }

  function isServerError() {
    return self::Error == $this->_status[0];
  }

  function appendHeader($_name_, $_value_) {
    $this->_headers[$_name_] = $_value_;
  }

  function clear() {
    throw new Narvalo\NotImplementedException();
  }

  function clearHeaders() {
    throw new Narvalo\NotImplementedException();
  }

  function clearContent() {
    throw new Narvalo\NotImplementedException();
  }

  function redirect($_url_) {
    throw new Narvalo\NotImplementedException();
  }

  function end() {
    throw new Narvalo\NotImplementedException();
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Addressing
// =================================================================================================

// {{{ Addr

interface IAddr {
  function getUrl();
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ Url

final class Url {
  private
    $_baseUrl,
    $_relativePath;

  function __construct($_baseUrl_, $_relativePath_) {
    $this->_baseUrl      = $_baseUrl_;
    $this->_relativePath = $_relativePath_;
  }

  function __toString() {
    return $this->_baseUrl . $this->_relativePath_;
  }
}

// }}} ---------------------------------------------------------------------------------------------

// Http clients
// =================================================================================================

// {{{ Curl

class Curl {
  protected $ch;

  function __construct() {
    $ch = \curl_init();

    if (!\is_resource($ch)) {
      throw new HttpException('Unable to create cURL handle.');
    }

    // Do not fail if the HTTP code returned is greater than or equal to 400.
    // The normal behaviour is to return the page normaly, ignoring the code.
    \curl_setopt($ch, \CURLOPT_FAILONERROR, \FALSE);
    // Return result upon execution.
    \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, \TRUE);
    // Response headers processing callback.
    \curl_setopt($ch, \CURLOPT_HEADERFUNCTION, 'Internal\curl_scan_header');

    $this->ch = $ch;
  }

  function __destruct() {
    if (\NULL !== $this->ch) {
      \curl_close($this->ch);
    }
  }

  function setOpt($_name_, $_value_) {
    if (\FALSE === \curl_setopt($this->ch, $_name_, $_value_)) {
      throw new HttpException("Unable to set option $_name_.");
    }
  }

  function setVerbose($_bool_) {
    $this->setOpt(\CURLOPT_VERBOSE, $_bool_);
  }

  function setTimeout($_timeout_) {
    $this->setOpt(\CURLOPT_TIMEOUT, $_timeout_);
  }

  function setConnectTimeout($_timeout_) {
    $this->setOpt(\CURLOPT_CONNECTTIMEOUT, $_timeout_);
  }

  function setMaxRedirects($_max_) {
    if ($_max_) {
      $this->setOpt(\CURLOPT_FOLLOWLOCATION, \TRUE);
      $this->setOpt(\CURLOPT_MAXREDIRS, $_max_);
    } else {
      $this->setOpt(\CURLOPT_FOLLOWLOCATION, \FALSE);
    }
  }

  function setCredentials($_username_, $_password_) {
    $this->setOpt(\CURLOPT_USERPWD, "$_username_:$_password_");
  }

  function setCookieJar() {
    ;
  }

  function execute(HttpRequest $_req_) {
    // Set method specific opts.
    switch ($_req_->getMethod()) {
    case 'GET':
      $this->setOpt(\CURLOPT_HTTPGET, \TRUE);
      break;
    case 'POST':
      $this->setOpt(\CURLOPT_POST, \TRUE);
      $this->setOpt(\CURLOPT_POSTFIELDS, $_req_->getContent());
      break;
    case 'HEAD':
      $this->setOpt(\CURLOPT_CUSTOMREQUEST, 'HEAD');
      throw new Narvalo\NotImplementedException('HEAD not yet implemented');
      break;
    case 'PUT':
      $this->setOpt(\CURLOPT_CUSTOMREQUEST, 'PUT');
      $this->setOpt(\CURLOPT_POSTFIELDS, $_req_->getContent());
      throw new Narvalo\NotImplementedException('PUT not yet implemented');
      break;
    case 'DELETE':
      $this->setOpt(\CURLOPT_CUSTOMREQUEST, 'DELETE');
      throw new Narvalo\NotImplementedException('DELETE not yet implemented');
      break;
    case 'TRACE':
      $this->setOpt(\CURLOPT_CUSTOMREQUEST, 'TRACE');
      throw new Narvalo\NotImplementedException('TRACE not yet implemented');
      break;
    default:
      throw new Narvalo\InvalidOperationException('Unknown HTTP verb.');
    }

    // Set URL.
    $this->setOpt(\CURLOPT_URL, $_req_->getUrl()->__toString());

    // Set headers.
    $headers = array();

    foreach ($_req_->getHeaders() as $k => $v) {
      $headers[] = "$k: $v";
    }

    $this->setOpt(\CURLOPT_HTTPHEADER, $headers);

    // Execution.
    $curl = new _\CurlHelper();
    $curl->reset();

    $body  = \curl_exec($this->ch);
    $errno = \curl_errno($this->ch);

    if (\FALSE === $body || $errno) {
      // Client request error
      $msg = 'Code: ' . $errno . ' ' . \curl_error($this->ch);
      throw new HttpException($msg);
    } else {
      // We've got an answer from the server beware it does not mean a successful one...

      $rsp = $curl->getResponse();
      $rsp->setBody($body);
      return $rsp;
    }
  }

  function doTRACE(URL $_url_) {
    $req = new HttpRequest('TRACE', $_url_);
    return $this->execute($req);
  }

  function doHEAD(URL $_url_) {
    $req = new HttpRequest('HEAD', $_url_);
    return $this->execute($req);
  }

  function doGET(URL $_url_) {
    $req = new HttpRequest('GET', $_url_);
    return $this->execute($req);
  }

  function doPOST(URL $_url_, $_content_) {
    $req = new HttpRequest('POST', $_url_);
    $req->setContent($_content_);
    return $this->execute($_req_);
  }

  function doPUT(URL $_url_) {
    $req = new HttpRequest('PUT', $_url_);
    return $this->execute($req);
  }

  function doDELETE(URL $_url_) {
    $req = new HttpRequest('DELETE', $_url_);
    return $this->execute($req);
  }
}

// }}} ---------------------------------------------------------------------------------------------

// #################################################################################################

namespace Narvalo\Web\Internal;

// {{{ CurlHelper

class CurlHelper {
  private static $_SharedResponse;
  private $_response;

  function __construct() {
    if (\NULL === $_SharedResponse) {
      self::$_SharedResponse = new HttpResponse();
    }

    $this->_response =& self::$_SharedResponse;
  }

  function getResponse() {
    return $this->_response;
  }

  function setProtocol($_protocol_) {
    $this->_response->setProtocol($_protocol_);
  }

  function setStatus($_status_) {
    $this->_response->setStatus($_status_);
  }

  function setMessage($_message_) {
    $this->_response->setMessage($_message_);
  }

  function setHeader($_name_, $_value_) {
    $this->_response->setHeader($_name_, $_value_);
  }

  function reset() {
    $this->_response->reset();
  }
}

// }}} ---------------------------------------------------------------------------------------------
// {{{ curl_scan_header()

/// Parser for the HTTP response preamble.
function curl_scan_header($_ch_, $_line_) {
  // NB: Each preamble line ends with a CRLF, the first empty line (after removing the CRLF)
  // marks the preamble's end.
  if ($line = \substr($_line_, 0, -2)) {
    // Beware, we do not enforce HTTP spec validation.

    $helper = new CurlHelper();

    if (   'HTTP' == \substr($line, 0, 4)
      && \FALSE !== ($fields = \split("\x20", $line, 3))
    ) {
      // Status line.
      $helper->setProtocol($fields[0]);
      $helper->setStatus($fields[1]);
      $helper->setMessage($fields[2]);
    } elseif (\FALSE !== ($header = \split(':', $line, 2))) {
      // Header line.
      $helper->setHeader($header[0], $header[1]);
    }
  }

  return \strlen($_line_);
}

// }}} ---------------------------------------------------------------------------------------------

// EOF

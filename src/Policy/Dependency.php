<?php

namespace Drutiny\Policy;

use Drutiny\Sandbox\Sandbox;
use Drutiny\ExpressionLanguage;
use Drutiny\Audit;
use Drutiny\Container;

class Dependency {

  /**
   * On fail behaviour: Omit policy from report.
   */
  const ON_FAIL_DEFAULT = 'fail';

  /**
   * On fail behaviour: Report policy as error.
   */
  const ON_FAIL_ERROR = 'error';

  /**
   * On fail behaviour: Report as not applicable.
   */
  const ON_FAIL_REPORT_ONLY = 'report_only';

  /**
   * @var string Must be one of ON_FAIL constants.
   */
  protected $onFail = 'error';

  /**
   * @var string Symfony ExpressionLanguage expression.
   */
  protected $expression;

  public function __construct($expression = 'true', $on_fail = self::ON_FAIL_DEFAULT)
  {
    $this->expression = $expression;
    $this->setFailBehaviour($on_fail);
  }

  public function getExpression()
  {
    return $this->expression;
  }

  public function getFailBehaviour()
  {
    switch ($this->onFail) {
      case self::ON_FAIL_ERROR:
        return Audit::ERROR;
      case self::ON_FAIL_REPORT_ONLY:
        return Audit::NOT_APPLICABLE;
      case self::ON_FAIL_DEFAULT;
      default:
        return Audit::FAIL;
    }
  }

  public function setFailBehaviour($on_fail = self::ON_FAIL_DEFAULT)
  {
    switch ($on_fail) {
      case self::ON_FAIL_ERROR:
      case self::ON_FAIL_DEFAULT:
      case self::ON_FAIL_REPORT_ONLY:
        $this->onFail = $on_fail;
        return $this;
      default:
        throw new \Exception("Unknown behaviour: $on_fail.");
    }
  }

  public function execute(Sandbox $sandbox)
  {
    $language = new ExpressionLanguage($sandbox);

    Container::getLogger()->info("Evaluating expression: " . $language->compile($this->expression));

    if ($return = $language->evaluate($this->expression)) {
      Container::getLogger()->debug("Expression PASSED: $return");
      return $return;
    }
    Container::getLogger()->debug("Expression FAILED.");
    throw new DependencyException($this);
  }
}

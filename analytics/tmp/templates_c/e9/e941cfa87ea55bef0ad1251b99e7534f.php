<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* @MobileMessaging/reportParametersScheduledReports.twig */
class __TwigTemplate_0b880310cf4cb2d6af7ed4f6c8fa83d4 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        yield "<div
  vue-entry=\"MobileMessaging.ReportParameters\"
  phone-numbers=\"";
        // line 3
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("phoneNumbers", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["phoneNumbers"]) || array_key_exists("phoneNumbers", $context) ? $context["phoneNumbers"] : (function () { throw new RuntimeError('Variable "phoneNumbers" does not exist.', 3, $this->source); })()), [])) : ([]))), "html_attr");
        yield "\"
></div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@MobileMessaging/reportParametersScheduledReports.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div
  vue-entry=\"MobileMessaging.ReportParameters\"
  phone-numbers=\"{{ phoneNumbers|default([])|json_encode|e('html_attr') }}\"
></div>
", "@MobileMessaging/reportParametersScheduledReports.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\MobileMessaging\\templates\\reportParametersScheduledReports.twig");
    }
}

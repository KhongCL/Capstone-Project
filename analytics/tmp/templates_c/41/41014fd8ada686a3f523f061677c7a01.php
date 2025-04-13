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

/* @CoreHome/_periodSelect.twig */
class __TwigTemplate_5ded4f66b5d0243113979aa1a2151f85 extends Template
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
    id=\"periodString\"
    vue-entry=\"CoreHome.PeriodSelector\"
    periods=\"";
        // line 4
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["enabledPeriods"]) || array_key_exists("enabledPeriods", $context) ? $context["enabledPeriods"] : (function () { throw new RuntimeError('Variable "enabledPeriods" does not exist.', 4, $this->source); })())), "html_attr");
        yield "\"
    class=\"borderedControl piwikTopControl\"
>
</div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@CoreHome/_periodSelect.twig";
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
        return array (  43 => 4,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div
    id=\"periodString\"
    vue-entry=\"CoreHome.PeriodSelector\"
    periods=\"{{ enabledPeriods|json_encode|e('html_attr') }}\"
    class=\"borderedControl piwikTopControl\"
>
</div>
", "@CoreHome/_periodSelect.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\CoreHome\\templates\\_periodSelect.twig");
    }
}

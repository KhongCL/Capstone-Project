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

/* @TagManager/helpContent.twig */
class __TwigTemplate_85c9c4ad5a87c7c303d6afb055e2dcf8 extends Template
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
        if ((array_key_exists("subHeading", $context) && (isset($context["subHeading"]) || array_key_exists("subHeading", $context) ? $context["subHeading"] : (function () { throw new RuntimeError('Variable "subHeading" does not exist.', 1, $this->source); })()))) {
            // line 2
            yield "<p><strong>";
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["subHeading"]) || array_key_exists("subHeading", $context) ? $context["subHeading"] : (function () { throw new RuntimeError('Variable "subHeading" does not exist.', 2, $this->source); })()), "html", null, true);
            yield "</strong></p>
";
        }
        // line 4
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context["paragraphs"]) || array_key_exists("paragraphs", $context) ? $context["paragraphs"] : (function () { throw new RuntimeError('Variable "paragraphs" does not exist.', 4, $this->source); })()));
        foreach ($context['_seq'] as $context["_key"] => $context["paragraph"]) {
            // line 5
            yield "<p>";
            yield $context["paragraph"];
            yield "</p>
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['paragraph'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@TagManager/helpContent.twig";
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
        return array (  50 => 5,  46 => 4,  40 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% if subHeading is defined and subHeading %}
<p><strong>{{ subHeading }}</strong></p>
{% endif %}
{% for paragraph in paragraphs %}
<p>{{ paragraph|raw }}</p>
{% endfor %}", "@TagManager/helpContent.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\TagManager\\templates\\helpContent.twig");
    }
}

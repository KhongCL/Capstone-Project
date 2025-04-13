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

/* @CoreHome/_topScreen.twig */
class __TwigTemplate_eb9a330987d536fa51182223aa725b5f extends Template
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
        yield "<nav>
    <div class=\"nav-wrapper\">
        <a href=\"#main\" tabindex=\"1\" class=\"accessibility-skip-to-content\">";
        // line 3
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreHome_SkipToContent"), "html", null, true);
        yield "</a>
        ";
        // line 4
        yield from         $this->loadTemplate("@CoreHome/_logo.twig", "@CoreHome/_topScreen.twig", 4)->unwrap()->yield($context);
        // line 5
        yield "        ";
        yield from         $this->loadTemplate("@CoreHome/_topBar.twig", "@CoreHome/_topScreen.twig", 5)->unwrap()->yield($context);
        // line 6
        yield "    </div>
</nav>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@CoreHome/_topScreen.twig";
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
        return array (  51 => 6,  48 => 5,  46 => 4,  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<nav>
    <div class=\"nav-wrapper\">
        <a href=\"#main\" tabindex=\"1\" class=\"accessibility-skip-to-content\">{{'CoreHome_SkipToContent'|translate}}</a>
        {% include \"@CoreHome/_logo.twig\" %}
        {% include \"@CoreHome/_topBar.twig\" %}
    </div>
</nav>", "@CoreHome/_topScreen.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\CoreHome\\templates\\_topScreen.twig");
    }
}

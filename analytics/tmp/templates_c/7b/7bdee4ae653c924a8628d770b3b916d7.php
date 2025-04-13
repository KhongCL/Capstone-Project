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

/* genericForm.twig */
class __TwigTemplate_7d402cb645f53090b836abae7649b46c extends Template
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
        if (CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 1, $this->source); })()), "errors", [], "any", false, false, false, 1)) {
            // line 2
            yield "\t<div class=\"alert alert-warning\">
\t\t<strong>";
            // line 3
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_PleaseFixTheFollowingErrors"), "html", null, true);
            yield ":</strong>
\t\t<ul>
            ";
            // line 5
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 5, $this->source); })()), "errors", [], "any", false, false, false, 5));
            foreach ($context['_seq'] as $context["_key"] => $context["data"]) {
                // line 6
                yield "\t\t\t\t<li>";
                yield $context["data"];
                yield "</li>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['data'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 8
            yield "\t\t</ul>
\t</div>
";
        }
        // line 11
        yield "
<form ";
        // line 12
        yield CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 12, $this->source); })()), "attributes", [], "any", false, false, false, 12);
        yield ">
    ";
        // line 14
        yield "    ";
        yield Twig\Extension\CoreExtension::join(CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 14, $this->source); })()), "hidden", [], "any", false, false, false, 14));
        yield "

    ";
        // line 16
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context["element_list"]) || array_key_exists("element_list", $context) ? $context["element_list"] : (function () { throw new RuntimeError('Variable "element_list" does not exist.', 16, $this->source); })()));
        foreach ($context['_seq'] as $context["_key"] => $context["fieldname"]) {
            // line 17
            yield "        ";
            if (CoreExtension::getAttribute($this->env, $this->source, ($context["form_data"] ?? null), $context["fieldname"], [], "array", true, true, false, 17)) {
                // line 18
                yield "            <div class=\"row form-group\">
                <div class=\"col s12 m12 l6\">
                    ";
                // line 20
                if ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 20, $this->source); })()), $context["fieldname"], [], "array", false, false, false, 20), "type", [], "any", false, false, false, 20) == "checkbox")) {
                    // line 21
                    yield "                        <label class=\"checkbox\">
                            ";
                    // line 22
                    yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 22, $this->source); })()), $context["fieldname"], [], "array", false, false, false, 22), "html", [], "any", false, false, false, 22);
                    yield "
                        </label>
                    ";
                } elseif (CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source,                 // line 24
(isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 24, $this->source); })()), $context["fieldname"], [], "array", false, false, false, 24), "label", [], "any", false, false, false, 24)) {
                    // line 25
                    yield "                        <label>
                            ";
                    // line 26
                    yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 26, $this->source); })()), $context["fieldname"], [], "array", false, false, false, 26), "label", [], "any", false, false, false, 26);
                    yield "
                        </label>
                        ";
                    // line 28
                    yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 28, $this->source); })()), $context["fieldname"], [], "array", false, false, false, 28), "html", [], "any", false, false, false, 28);
                    yield "
                    ";
                } elseif ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source,                 // line 29
(isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 29, $this->source); })()), $context["fieldname"], [], "array", false, false, false, 29), "type", [], "any", false, false, false, 29) == "hidden")) {
                    // line 30
                    yield "                        ";
                    yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 30, $this->source); })()), $context["fieldname"], [], "array", false, false, false, 30), "html", [], "any", false, false, false, 30);
                    yield "
                    ";
                }
                // line 32
                yield "                </div>
            </div>
        ";
            }
            // line 35
            yield "    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['fieldname'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 36
        yield "
    ";
        // line 37
        if (CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 37, $this->source); })()), "submit", [], "any", false, false, false, 37), "html", [], "any", false, false, false, 37)) {
            // line 38
            yield "        <div class=\"row\">
            <div class=\"col s12\">
                ";
            // line 40
            yield CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["form_data"]) || array_key_exists("form_data", $context) ? $context["form_data"] : (function () { throw new RuntimeError('Variable "form_data" does not exist.', 40, $this->source); })()), "submit", [], "any", false, false, false, 40), "html", [], "any", false, false, false, 40);
            yield "
            </div>
        </div>
    ";
        }
        // line 44
        yield "</form>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "genericForm.twig";
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
        return array (  149 => 44,  142 => 40,  138 => 38,  136 => 37,  133 => 36,  127 => 35,  122 => 32,  116 => 30,  114 => 29,  110 => 28,  105 => 26,  102 => 25,  100 => 24,  95 => 22,  92 => 21,  90 => 20,  86 => 18,  83 => 17,  79 => 16,  73 => 14,  69 => 12,  66 => 11,  61 => 8,  52 => 6,  48 => 5,  43 => 3,  40 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% if form_data.errors %}
\t<div class=\"alert alert-warning\">
\t\t<strong>{{ 'Installation_PleaseFixTheFollowingErrors'|translate }}:</strong>
\t\t<ul>
            {% for data in form_data.errors %}
\t\t\t\t<li>{{ data|raw }}</li>
            {% endfor %}
\t\t</ul>
\t</div>
{% endif %}

<form {{ form_data.attributes|raw }}>
    {# display any hidden input field #}
    {{ form_data.hidden|join|raw }}

    {% for fieldname in element_list %}
        {% if form_data[fieldname] is defined %}
            <div class=\"row form-group\">
                <div class=\"col s12 m12 l6\">
                    {% if form_data[fieldname].type == 'checkbox' %}
                        <label class=\"checkbox\">
                            {{ form_data[fieldname].html|raw }}
                        </label>
                    {% elseif form_data[fieldname].label %}
                        <label>
                            {{ form_data[fieldname].label|raw }}
                        </label>
                        {{ form_data[fieldname].html|raw }}
                    {% elseif form_data[fieldname].type == 'hidden' %}
                        {{ form_data[fieldname].html|raw }}
                    {% endif %}
                </div>
            </div>
        {% endif %}
    {% endfor %}

    {% if form_data.submit.html %}
        <div class=\"row\">
            <div class=\"col s12\">
                {{ form_data.submit.html|raw }}
            </div>
        </div>
    {% endif %}
</form>
", "genericForm.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\Morpheus\\templates\\genericForm.twig");
    }
}

<?php declare(strict_types=1);

namespace YeAPF\Services;

class RouteDefinition {
    private string $path;
    private $handler;
    private array $methods;
    private ?string $authMethod = null;
    private array $allowedSections = [];

    public function __construct(string $path, callable $handler, array $methods) {
        $this->path = $path;
        $this->handler = $handler;
        $this->methods = $methods;
    }

    public function requireAuth(string $authMethod): self {
        $this->authMethod = $authMethod;
        return $this;
    }

    public function addAllowedSection(string $section): self {
        $this->allowedSections[] = $section;
        return $this;
    }

    public function getAuthMethod(): ?string {
        return $this->authMethod;
    }

    public function isSectionAllowed(string $section): bool {
        return empty($this->allowedSections) || in_array($section, $this->allowedSections, true);
    }
}

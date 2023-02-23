<?php

namespace Hyqo\Cache;

class CacheItem
{
    protected array $tags = [];

    protected ?int $expiresAt = null;
    protected ?int $expiresAfter = null;

    public function __construct(
        public readonly string $key,
        public readonly bool $isHit = false,
        protected mixed $value = null,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function set(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    public function getExpiresAfter(): ?int
    {
        return $this->expiresAfter;
    }

    protected function setEternal(): static
    {
        $this->expiresAt = null;
        $this->expiresAfter = null;

        return $this;
    }

    public function expiresAt(?int $timestamp): self
    {
        if (null === $timestamp) {
            return $this->setEternal();
        }

        $this->expiresAt = $timestamp;
        $this->expiresAfter = $timestamp ? $timestamp - time() : 0;

        return $this;
    }

    public function expiresAfter(?int $seconds): self
    {
        if (null === $seconds) {
            return $this->setEternal();
        }

        $this->expiresAt = $seconds ? time() + $seconds : 0;
        $this->expiresAfter = $seconds;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string|string[] $tag
     * @return $this
     */
    public function tag(string|array $tag): static
    {
        if (is_string($tag)) {
            $tags = [$tag];
        } else {
            $tags = array_values($tag);
        }

        $this->tags = array_unique([...$this->tags, ...$tags]);

        return $this;
    }
}

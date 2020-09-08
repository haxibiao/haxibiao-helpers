<?php
namespace Haxibiao\Helpers\Traits;

use Closure;
use InvalidArgumentException;

trait CanCacheAttributes
{
    /**
     * 需要缓存的属性
     * @var array
     */
    protected $attributeCache = [];

    /**
     * 缓存Store(不是必须)
     * @var array
     */
    protected $attributeCacheStore=null;

    public static function bootCanCacheAttributes(): void
    {
        // 当发生删除操作时清理缓存
        static::deleting(function ($model): void {
            $model->flush();
        });
    }

    /**
     * 保存缓存
     * @param string $attribute
     * @param int|null $ttl
     * @param Closure $callback
     * @return mixed
     */
    public function remember(string $attribute, ?int $ttl, Closure $callback)
    {
        if ($ttl < 0) {
            throw new InvalidArgumentException('您的输入有误');
        }

        if ($ttl === 0 || ! $this->exists) {
            if (! isset($this->attributeCache[$attribute])) {
                $this->attributeCache[$attribute] = value($callback);
            }
            return $this->attributeCache[$attribute];
        }

        if ($ttl === null) {
            return $this->getCacheRepository()->rememberForever($this->getCacheKey($attribute), $callback);
        }

        return $this->getCacheRepository()->remember($this->getCacheKey($attribute), $ttl, $callback);
    }

    /**
     * 永久保存
     * @param string $attribute
     * @param Closure $callback
     * @return mixed
     */
    public function rememberForever(string $attribute, Closure $callback)
    {
        return $this->remember($attribute, null, $callback);
    }

    /**
     * 移除缓存
     * @param string $attribute
     * @return bool
     */
    public function forget(string $attribute): bool
    {
        unset($this->attributeCache[$attribute]);

        if (! $this->exists) {
            return true;
        }

        return $this->getCacheRepository()->forget($this->getCacheKey($attribute));
    }

    /**
     * 刷新缓存
     * @return bool
     */
    public function flush(): bool
    {
        $result = true;

        foreach ($this->cachableAttributes ?? [] as $attribute) {
            $result = $this->forget($attribute) ? $result : false;
        }

        return $result;
    }

    /**
     * 拼接CacheKey
     * @param string $attribute
     * @return string
     */
    protected function getCacheKey(string $attribute): string
    {
        return implode('.', [
            $this->attributeCachePrefix ?? 'model_attribute_cache',
            $this->getConnectionName() ?? 'connection',
            $this->getTable(),
            $this->getKey(),
            $attribute,
        ]);
    }

    protected function getCacheRepository()
    {
        // attributeCacheStore为空就使用cache config中的默认的缓存介质
        return app('cache')->store($this->attributeCacheStore);
    }
}
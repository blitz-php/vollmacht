<?php

namespace BlitzPHP\Vollmacht\Bridge;

use BlitzPHP\Utilities\DateTime\Date;
use BlitzPHP\Vollmacht\Entities\DeviceCode as DeviceCodeEntity;
use BlitzPHP\Vollmacht\Vollmacht;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;

class DeviceCodeRepository implements DeviceCodeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNewDeviceCode(): DeviceCodeEntityInterface
    {
        return new DeviceCode;
    }

    /**
     * {@inheritdoc}
     */
    public function persistDeviceCode(DeviceCodeEntityInterface $deviceCodeEntity): void
    {
        if (! is_null($deviceCodeEntity->getUserIdentifier())) {
            Vollmacht::deviceCode()->newQuery()->whereKey($deviceCodeEntity->getIdentifier())->update([
                'user_id' => $deviceCodeEntity->getUserIdentifier(),
                'user_approved_at' => $deviceCodeEntity->getUserApproved() ? Date::now() : null,
            ]);
        } elseif (! is_null($deviceCodeEntity->getLastPolledAt())) {
            Vollmacht::deviceCode()->newQuery()->whereKey($deviceCodeEntity->getIdentifier())->update([
                'last_polled_at' => $deviceCodeEntity->getLastPolledAt(),
            ]);
        } else {
            Vollmacht::deviceCode()->forceFill([
                'id' => $deviceCodeEntity->getIdentifier(),
                'user_id' => null,
                'client_id' => $deviceCodeEntity->getClient()->getIdentifier(),
                'user_code' => $deviceCodeEntity->getUserCode(),
                'scopes' => $deviceCodeEntity->getScopes(),
                'revoked' => false,
                'user_approved_at' => null,
                'last_polled_at' => null,
                'expires_at' => $deviceCodeEntity->getExpiryDateTime(),
            ])->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDeviceCodeEntityByDeviceCode(string $deviceCode): ?DeviceCodeEntityInterface
    {
        $record = Vollmacht::deviceCode()->newQuery()->whereKey($deviceCode)->where(['revoked' => false])->first();

        return $record ? $this->fromDeviceCodeEntity($record) : null;
    }

    /*
     * Get the league device code by the given user code.
     */
    public function getDeviceCodeEntityByUserCode(string $userCode): ?DeviceCodeEntityInterface
    {
        $record = Vollmacht::deviceCode()->newQuery()
            ->where('user_code', $userCode)
            ->whereNull('user_id')
            ->where('expires_at', '>', Date::now())
            ->where('revoked', false)
            ->first();

        return $record ? $this->fromDeviceCodeEntity($record) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeDeviceCode(string $codeId): void
    {
        Vollmacht::deviceCode()->newQuery()->whereKey($codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isDeviceCodeRevoked(string $codeId): bool
    {
        return Vollmacht::deviceCode()->newQuery()->whereKey($codeId)->where('revoked', false)->doesntExist();
    }

    /**
     * Create a new league device code from the given device code entity instance.
     */
    protected function fromDeviceCodeEntity(DeviceCodeEntity $entity): DeviceCodeEntityInterface
    {
        return new DeviceCode(
            $entity->getKey(),
            $entity->user_id,
            $entity->client_id,
            $entity->scopes,
            ! is_null($entity->user_approved_at),
            $entity->last_polled_at?->toDateTimeImmutable(),
            $entity->expires_at?->toDateTimeImmutable()
        );
    }
}

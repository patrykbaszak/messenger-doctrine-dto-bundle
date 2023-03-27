<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PBaszak\MessengerDoctrineDTOBundle\Contract\GetObjects;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Persister\SqlExpressionVisitor;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetDTODQL;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[AsMessageHandler()]
class GetObjectsHandler
{
    use HandleTrait;
    use GetTargetEntity;

    public function __construct(
        private EntityManagerInterface $_em,
        private DenormalizerInterface $denormalizer,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    /** @return mixed[] */
    public function __invoke(GetObjects $message): array
    {
        $outputClass = $message->instanceOf;

        $dql = $this->handle(new GetDTODQL($outputClass));
        if (null !== $message->criteria) {
            $expression = $message->criteria->getWhereExpression();
            $output = $expression?->visit(new SqlExpressionVisitor($dql));

            if (null !== $output) {
                $dql .= ' WHERE '.$output;
            }
        }

        $query = $this->_em->createQuery($dql);
        $output = $query->execute(null, Query::HYDRATE_ARRAY);

        if ($message->arrayHydration) {
            return array_map(fn (array $item) => $this->nestKeys($item), $output);
        }

        return array_map(
            fn (array $item) => $this->denormalizer->denormalize(
                $this->nestKeys($item),
                $outputClass
            ),
            $output
        );
    }

    /**
     * @param array<string,mixed> $inputArray
     *
     * @return array<string,mixed>
     */
    public function nestKeys(array $inputArray): array
    {
        $outputArray = [];

        foreach ($inputArray as $key => $value) {
            $keys = explode('__', (string) $key);
            /** @var array<string,mixed> $nestedArray */
            $nestedArray = &$outputArray;

            foreach ($keys as $innerKey) {
                /** @var string $innerKey */
                if (!isset($nestedArray[$innerKey])) {
                    $nestedArray[$innerKey] = [];
                }
                $nestedArray = &$nestedArray[$innerKey];
            }

            $nestedArray = $value;
        }

        return $outputArray;
    }
}

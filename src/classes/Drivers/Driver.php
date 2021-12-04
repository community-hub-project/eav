<?php
declare(strict_types=1);

namespace CommunityHub\Eav\Drivers;

use CommunityHub\Eav\Entity;
use CommunityHub\Eav\Query;

/**
 * Interface which all storage strategies must implement.
 */
interface Driver
{
    /**
     * Execute a query and return the matching entities as an array.
     *
     * @param string $pot The name of the pot to execute the query on.
     * @param Query $query The query to execute on the data store.
     * @param int $offset The first record to return, defaults to 0.
     * @param ?int $length The last record to return, if null, then return
     *     all entities from the offset to the end of the result set.
     * @return array The query results.
     * @throws Exception If there is an error returning the query results.
     */
    public function get(string $pot, Query $query, int $offset = 0, ?int $length = null): array;

    /**
     * Persist the entities to the data store.
     *
     * @param string $pot The name of the pot to execute the query on.
     * @param Entity ...$entities The entities to put into the data store.
     * @return array A string array of all the UIDs of the persisted entities.
     *     The UIDs should be in the same position in the array as the entities
     *     that were passed into the method. If any entities to be persisted
     *     have a UID of null. The the driver MUST generate a unique UID
     *     and return it.
     * @throws Exception If there is an error persisting the entities.
     */
    public function put(string $pot, Entity ...$entities): array;

    /**
     * Execute a query and delete the matching entities.
     *
     * @param string $pot The name of the pot to execute the query on.
     * @param Query $query The query to execute on the data store.
     * @throws Exception If there is an error deleting the query results.
     * @return static
     */
    public function remove(string $pot, Query $query): static;
}

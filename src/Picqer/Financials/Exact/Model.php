<?php namespace Picqer\Financials\Exact;

abstract class Model
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array The model's attributes
     */
    protected $attributes = array();

    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = array();

    /**
     * @var string The URL endpoint of this model
     */
    protected $url = '';

    /**
     * @var string Name of the primary key for this model
     */
    protected $primaryKey = 'ID';


    public function __construct(Connection $connection, array $attributes = array())
    {
        $this->connection = $connection;
        $this->fill($attributes);
    }


    /**
     * Get the connection instance
     *
     * @return Connection
     */
    public function connection()
    {
        return $this->connection;
    }


    /**
     * Get the model's attributes
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }


    /**
     * Fill the entity from an array
     *
     * @param array $attributes
     */
    protected function fill(array $attributes)
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
    }


    /**
     * Get the fillable attributes of an array
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        if (count($this->fillable) > 0) {
            return array_intersect_key($attributes, array_flip($this->fillable));
        }

        return $attributes;
    }


    protected function isFillable($key)
    {
        return in_array($key, $this->fillable);
    }


    protected function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }


    public function __get($key)
    {
        if (isset( $this->attributes[$key] )) {
            return $this->attributes[$key];
        }
    }


    public function __set($key, $value)
    {
        if ($this->isFillable($key)) {
            return $this->setAttribute($key, $value);
        }
    }


    public function exists()
    {
        if ( ! array_key_exists($this->primaryKey, $this->attributes)) {
            return false;
        }

        return ! empty( $this->attributes[$this->primaryKey] );
    }


    public function json()
    {
        return json_encode($this->attributes);
    }

    public function save()
    {
        if ($this->exists())
        {
            $this->fill($this->update());
        } else
        {
            $this->fill($this->insert());
        }

        return $this;
    }

    public function insert()
    {
        return $this->connection()->post($this->url, $this->json());
    }

    public function update()
    {
        $key = $this->primaryKey;
        $primarykey = $this->$key;

        if (!empty($this->attributes[$this->primaryKey]))
            unset($this->attributes[$this->primaryKey]);

        return $this->connection()->put($this->url . "(guid'$primarykey')", $this->json());
    }

    public function delete()
    {
        $key = $this->primaryKey;
        $primarykey = $this->$key;

        return $this->connection()->delete($this->url . "(guid'$primarykey')");
    }


    public function find($id)
    {
        $result = $this->connection()->get($this->url, array(
            '$filter' => $this->primaryKey . " eq guid'$id'"
        ));

        return new self($this->connection(), $result);
    }


    public function filter($filter, $expand = '', $select = '')
    {
        $request = array(
            '$filter' => $filter
        );
        if (strlen($expand) > 0) {
            $request['$expand'] = $expand;
        }
        if (strlen($select) > 0) {
            $request['$select'] = $select;
        }

        $result = $this->connection()->get($this->url, $request);

        // If we have one result which is not an assoc array, make it the first element of an array for the
        // collectionFromResult function so we always return a collection from filter
        if ((bool) count(array_filter(array_keys($result), 'is_string'))) {
            $result = array($result);
        }

        return $this->collectionFromResult($result);
    }


    public function filterAll($filter, $expand = '', $select = '')
    {
        $request = array(
            '$filter' => $filter
        );
        if (strlen($expand) > 0) {
            $request['$expand'] = $expand;
        }
        if (strlen($select) > 0) {
            $request['$select'] = $select;
        }

        $result = $this->connection()->getAll($this->url, $request);

        // If we have one result which is not an assoc array, make it the first element of an array for the
        // collectionFromResult function so we always return a collection from filter
        if ((bool) count(array_filter(array_keys($result), 'is_string'))) {
            $result = array($result);
        }

        return $this->collectionFromResult($result);
    }


    public function get()
    {
        $result = $this->connection()->get($this->url);

        return $this->collectionFromResult($result);
    }


    public function collectionFromResult($result)
    {
        $model = get_class($this);

        $collection = array();
        foreach ($result as $r) {
            $collection[] = new $model($this->connection(), $r);
        }

        return $collection;
    }

}
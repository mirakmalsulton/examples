<?php

namespace App\Src\Entities;

use App\Utils\UUID;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kyslik\ColumnSortable\Sortable;

/**
 * @property string id
 * @property string phone
 * @property string name
 * @property int rating
 * @property string comment
 * @property string created_at
 * @property string updated_at
 */
class Client extends Model
{
    use HasFactory;
    use Sortable;

    public $incrementing = false;

    protected $table = 'client';
    protected $keyType = 'string';

    protected $fillable = ['phone', 'name', 'created_at'];

    private array $ratingList = [
        1 => 'Плохой',
        2 => 'Средний',
        3 => 'Хороший',
    ];

    public static function make(UUID $uuid, string $phone, string $name): self
    {
        $client = new static();
        $client->id = $uuid->getNext();
        $client->phone = $phone;
        $client->name = $name;
        $client->rating = 0;
        return $client;
    }

    public function changePhone(string $phone)
    {
        $this->phone = $phone;
    }

    public function changeName(string $name)
    {
        $this->name = $name;
    }

    public function changeRating(int $rating)
    {
        if ($rating < 0 || $rating > 5) throw new InvalidArgumentException('Invalid rating');
        $this->rating = $rating;
    }

    public function getRatingList(): array
    {
        return $this->ratingList;
    }

    public function getRatingNameByKey(int $key)
    {
        if(array_key_exists($key, $this->ratingList)) return $this->ratingList[$key];
        return null;
    }


    protected static function newFactory(): Factory
    {
        return ClientFactory::new();
    }

}

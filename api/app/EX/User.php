<?php

namespace App\EX;

use Illuminate\Support\Facades\Validator;

class User
{
    /**
     * @var int
     */
    private $id;

    /**
     * User constructor.
     * @param int $id
     */
    public function __construct(int $id = null)
    {
        if (!empty($id)) {
            $this->setId($id);
        } else {
            $this->id = null;
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $data_src = [
          'id' => $id,
        ];
        $validator = Validator::make($data_src, [
          'id' => 'required|numeric',
        ])->validate();

        $this->id = $id;
    }

    /**
     * @return \stdClass object
     */
    public function getUser(): object
    {
        $data_src = [
          'id' => $this->getId(),
        ];
        $validator = Validator::make($data_src, [
          'id' => 'required|numeric',
        ])->validate();

        $result = \App\User::where('id', $this->getId())->first()->toArray();
        return (object)$result;
    }

    /**
     * @param array $request
     * @return \stdClass object
     */
    public function createUser(array $request): object
    {
        $validator = Validator::make($request, [
          'name' => 'required',
        ])->validate();
        $userR = (object)$request;

        $u = new \App\User;
        $u->name = $userR->name;
        if (!empty($userR->country)) {
            $u->country = $userR->country;
        }
        if (!empty($userR->city)) {
            $u->city = $userR->city;
        }
        $u->save();

        $this->setId($u->id);
        $result = $this->getUser();

        return (object)$result;
    }
}
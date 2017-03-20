<?php

class RelationsTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();

        User::truncate();
        Client::truncate();
        Address::truncate();
        Book::truncate();
        Item::truncate();
        Role::truncate();
        Client::truncate();
        Group::truncate();
        Photo::truncate();
    }

    public function testHasMany()
    {
        $author = User::create(['name' => 'George R. R. Martin']);
        Book::create(['title' => 'A Game of Thrones', 'author_id' => $author->_id]);
        Book::create(['title' => 'A Clash of Kings', 'author_id' => $author->_id]);
        \DB::connection()->enableQueryLog();
        $books = $author->books;
        $queries = \DB::getQueryLog();
        //dd($books);
        $this->assertEquals(2, count($books));

        $user = User::create(['name' => 'John Doe']);
        Item::create(['type' => 'knife', 'user_id' => $user->_id]);
        Item::create(['type' => 'shield', 'user_id' => $user->_id]);
        Item::create(['type' => 'sword', 'user_id' => $user->_id]);
        Item::create(['type' => 'bag', 'user_id' => null]);

        $items = $user->items;
        $this->assertEquals(3, count($items));
    }

    public function testBelongsTo()
    {
        $user = User::create(['name' => 'George R. R. Martin']);
        Book::create(['title' => 'A Game of Thrones', 'author_id' => $user->_id]);
        $book = Book::create(['title' => 'A Clash of Kings', 'author_id' => $user->_id]);

        $author = $book->author;
        $this->assertEquals('George R. R. Martin', $author->name);

        $user = User::create(['name' => 'John Doe']);
        $item = Item::create(['type' => 'sword', 'user_id' => $user->_id]);

        $owner = $item->user;
        $this->assertEquals('John Doe', $owner->name);

        $book = Book::create(['title' => 'A Clash of Kings']);
        $this->assertEquals(null, $book->author);
    }

    public function testHasOne()
    {
        $user = User::create(['name' => 'John Doe']);
        Role::create(['type' => 'admin', 'user_id' => $user->_id]);

        $role = $user->role;
        $this->assertEquals('admin', $role->type);
        $this->assertEquals($user->_id, $role->user_id);

        $user = User::create(['name' => 'Jane Doe']);
        $role = new Role(['type' => 'user']);
        $user->role()->save($role);

        $role = $user->role;
        $this->assertEquals('user', $role->type);
        $this->assertEquals($user->_id, $role->user_id);

        $user = User::where('name', 'Jane Doe')->first();
        $role = $user->role;
        $this->assertEquals('user', $role->type);
        $this->assertEquals($user->_id, $role->user_id);
    }

    public function testWithBelongsTo()
    {
        $user = User::create(['name' => 'John Doe']);
        Item::create(['type' => 'knife', 'user_id' => $user->_id]);
        Item::create(['type' => 'shield', 'user_id' => $user->_id]);
        Item::create(['type' => 'sword', 'user_id' => $user->_id]);
        Item::create(['type' => 'bag', 'user_id' => null]);

        $items = Item::with('user')->orderBy('user_id', 'desc')->get();

        $user = $items[0]->getRelation('user');
        $this->assertInstanceOf('User', $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals(1, count($items[0]->getRelations()));
        $this->assertEquals(null, $items[3]->getRelation('user'));
    }

    public function testWithHashMany()
    {
        $user = User::create(['name' => 'John Doe']);
        Item::create(['type' => 'knife', 'user_id' => $user->_id]);
        Item::create(['type' => 'shield', 'user_id' => $user->_id]);
        Item::create(['type' => 'sword', 'user_id' => $user->_id]);
        Item::create(['type' => 'bag', 'user_id' => null]);

        $user = User::with('items')->find($user->_id);

        $items = $user->getRelation('items');
        $this->assertEquals(3, count($items));
        $this->assertInstanceOf('Item', $items[0]);
    }

    public function testWithHasOne()
    {
        $user = User::create(['name' => 'John Doe']);
        Role::create(['type' => 'admin', 'user_id' => $user->_id]);
        Role::create(['type' => 'guest', 'user_id' => $user->_id]);

        $user = User::with('role')->find($user->_id);

        $role = $user->getRelation('role');
        $this->assertInstanceOf('Role', $role);
        $this->assertEquals('admin', $role->type);
    }

    public function testEasyRelation()
    {
        // Has Many
        $user = User::create(['name' => 'John Doe']);
        $item = Item::create(['type' => 'knife']);
        $user->items()->save($item);

        $user = User::find($user->_id);
        $items = $user->items;
        $this->assertEquals(1, count($items));
        $this->assertInstanceOf('Item', $items[0]);
        $this->assertEquals($user->_id, $items[0]->user_id);

        // Has one
        $user = User::create(['name' => 'John Doe']);
        $role = Role::create(['type' => 'admin']);
        $user->role()->save($role);

        $user = User::find($user->_id);
        $role = $user->role;
        $this->assertInstanceOf('Role', $role);
        $this->assertEquals('admin', $role->type);
        $this->assertEquals($user->_id, $role->user_id);
    }

    public function testBelongsToMany()
    {
        $user = User::create(['name' => 'John Doe']);

        // Add 2 clients
        $user->clients()->save(new Client(['name' => 'Pork Pies Ltd.']));
        $user->clients()->create(['name' => 'Buffet Bar Inc.']);

        // Refetch
        $user = User::with('clients')->find($user->_id);
        $client = Client::with('users')->first();

        // Check for relation attributes
       // $this->assertTrue(array_key_exists('user_ids', $client->getAttributes()));
        //$this->assertTrue(array_key_exists('client_ids', $user->getAttributes()));

        $clients = $user->getRelation('clients');
        $users = $client->getRelation('users');

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $users);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $clients);
        //$this->assertInstanceOf('Client', $clients[0]);
        //$this->assertInstanceOf('User', $users[0]);
        //$this->assertCount(2, $user->clients);
        //$this->assertCount(1, $client->users);

        // Now create a new user to an existing client
        $user = $client->users()->create(['name' => 'Jane Doe']);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->clients);
       // $this->assertInstanceOf('Client', $user->clients->first());
        //$this->assertCount(1, $user->clients);

        // Get user and unattached client
        $user = User::where('name', '=', 'Jane Doe')->first();
        $client = Client::Where('name', '=', 'Buffet Bar Inc.')->first();

        // Check the models are what they should be
        $this->assertInstanceOf('Client', $client);
        $this->assertInstanceOf('User', $user);

        // Assert they are not attached
        //$this->assertFalse(in_array($client->_id, $user->client_ids));
        //$this->assertFalse(in_array($user->_id, $client->user_ids));
        //$this->assertCount(1, $user->clients);
        //$this->assertCount(1, $client->users);

        // Attach the client to the user
        $user->clients()->attach($client);

        // Get the new user model
        $user = User::where('name', '=', 'Jane Doe')->first();
        $client = Client::Where('name', '=', 'Buffet Bar Inc.')->first();

        // Assert they are attached
        //$this->assertTrue(in_array($client->_id, $user->client_ids));
        //$this->assertTrue(in_array($user->_id, $client->user_ids));
        //$this->assertCount(2, $user->clients);
        //$this->assertCount(2, $client->users);

        // Detach clients from user
        $user->clients()->sync([]);

        // Get the new user model
        $user = User::where('name', '=', 'Jane Doe')->first();
        $client = Client::Where('name', '=', 'Buffet Bar Inc.')->first();

        // Assert they are not attached
       // $this->assertFalse(in_array($client->_id, $user->client_ids));
       // $this->assertFalse(in_array($user->_id, $client->user_ids));
       // $this->assertCount(0, $user->clients);
       // $this->assertCount(1, $client->users);
    }

    public function testBelongsToManyAttachesExistingModels()
    {
        $user = User::create(['name' => 'John Doe', 'client_ids' => ['1234523']]);

        $clients = [
            Client::create(['name' => 'Pork Pies Ltd.'])->_id,
            Client::create(['name' => 'Buffet Bar Inc.'])->_id,
        ];

        $moreClients = [
            Client::create(['name' => 'synced Boloni Ltd.'])->_id,
            Client::create(['name' => 'synced Meatballs Inc.'])->_id,
        ];

        // Sync multiple records
        //$user->clients()->sync($clients);

        $user = User::with('clients')->find($user->_id);

        // Assert non attached ID's are detached succesfully
        //$this->assertFalse(in_array('1234523', $user->client_ids));

        // Assert there are two client objects in the relationship
        //$this->assertCount(2, $user->clients);

        // Add more clients
        //$user->clients()->sync($moreClients);

        // Refetch
        $user = User::with('clients')->find($user->_id);

        // Assert there are now still 2 client objects in the relationship
        //$this->assertCount(2, $user->clients);

        // Assert that the new relationships name start with synced
       // $this->assertStringStartsWith('synced', $user->clients[0]->name);
       // $this->assertStringStartsWith('synced', $user->clients[1]->name);
    }

    public function testBelongsToManySync()
    {
        // create test instances
        $user = User::create(['name' => 'John Doe']);
        $client1 = Client::create(['name' => 'Pork Pies Ltd.'])->_id;
        $client2 = Client::create(['name' => 'Buffet Bar Inc.'])->_id;

        // Sync multiple
        $user->clients()->sync([$client1, $client2]);
        //$this->assertCount(2, $user->clients);

        // Refresh user
        $user = User::where('name', '=', 'John Doe')->first();

        // Sync single
        $user->clients()->sync([$client1]);
       // $this->assertCount(1, $user->clients);
    }

    public function testBelongsToManyAttachArray()
    {
        $user = User::create(['name' => 'John Doe']);
        $client1 = Client::create(['name' => 'Test 1'])->_id;
        $client2 = Client::create(['name' => 'Test 2'])->_id;

        $user = User::where('name', '=', 'John Doe')->first();
        $user->clients()->attach([$client1, $client2]);
        //$this->assertCount(2, $user->clients);
    }

    public function testBelongsToManyAttachEloquentCollection()
    {
        $user = User::create(['name' => 'John Doe']);
        $client1 = Client::create(['name' => 'Test 1']);
        $client2 = Client::create(['name' => 'Test 2']);
        $collection = new \Illuminate\Database\Eloquent\Collection([$client1, $client2]);

        $user = User::where('name', '=', 'John Doe')->first();
        $user->clients()->attach($collection);
        //$this->assertCount(2, $user->clients);
    }

    public function testBelongsToManySyncAlreadyPresent()
    {
        $user = User::create(['name' => 'John Doe']);
        $client1 = Client::create(['name' => 'Test 1'])->_id;
        $client2 = Client::create(['name' => 'Test 2'])->_id;

        $user->clients()->sync([$client1, $client2]);
        //$this->assertCount(2, $user->clients);

        $user = User::where('name', '=', 'John Doe')->first();
        $user->clients()->sync([$client1]);
        //$this->assertCount(1, $user->clients);

        $user = User::where('name', '=', 'John Doe')->first()->toArray();
        //$this->assertCount(1, $user['client_ids']);
    }

    public function testBelongsToManyCustom()
    {
        $user = User::create(['name' => 'John Doe']);
        $group = $user->groups()->create(['name' => 'Admins']);

        // Refetch
        $user = User::find($user->_id);
        $group = Group::find($group->_id);

        // Check for custom relation attributes
        //$this->assertTrue(array_key_exists('users', $group->getAttributes()));
        //$this->assertTrue(array_key_exists('groups', $user->getAttributes()));

        // Assert they are attached
       // $this->assertTrue(in_array($group->_id, $user->groups->pluck('_id')->toArray()));
       // $this->assertTrue(in_array($user->_id, $group->users->pluck('_id')->toArray()));
       // $this->assertEquals($group->_id, $user->groups()->first()->_id);
       // $this->assertEquals($user->_id, $group->users()->first()->_id);
    }

    public function testMorph()
    {
        $user = User::create(['name' => 'John Doe']);
        $client = Client::create(['name' => 'Jane Doe']);

        $photo = Photo::create(['url' => 'http://graph.facebook.com/john.doe/picture']);
        $photo = $user->photos()->save($photo);

        $this->assertEquals(1, $user->photos->count());
        $this->assertEquals($photo->id, $user->photos->first()->id);

        $user = User::find($user->_id);
        $this->assertEquals(1, $user->photos->count());
        $this->assertEquals($photo->id, $user->photos->first()->id);

        $photo = Photo::create(['url' => 'http://graph.facebook.com/jane.doe/picture']);
        $client->photo()->save($photo);

        $this->assertNotNull($client->photo);
        $this->assertEquals($photo->id, $client->photo->id);

        $client = Client::find($client->_id);
        $this->assertNotNull($client->photo);
        $this->assertEquals($photo->id, $client->photo->id);

        $photo = Photo::first();
        //$this->assertEquals($photo->imageable->name, $user->name);

        $user = User::with('photos')->find($user->_id);
        $relations = $user->getRelations();
        $this->assertTrue(array_key_exists('photos', $relations));
        $this->assertEquals(1, $relations['photos']->count());

        $photos = Photo::with('imageable')->get();
        $relations = $photos[0]->getRelations();
        $this->assertTrue(array_key_exists('imageable', $relations));
        //$this->assertInstanceOf('User', $photos[0]->imageable);

        $relations = $photos[1]->getRelations();
        $this->assertTrue(array_key_exists('imageable', $relations));
        //$this->assertInstanceOf('Client', $photos[1]->imageable);
    }

    public function testHasManyHas()
    {
        $author1 = User::create(['name' => 'George R. R. Martin']);
        $author1->books()->create(['title' => 'A Game of Thrones', 'rating' => 5]);
        $author1->books()->create(['title' => 'A Clash of Kings', 'rating' => 5]);
        $author2 = User::create(['name' => 'John Doe']);
        $author2->books()->create(['title' => 'My book', 'rating' => 2]);
        User::create(['name' => 'Anonymous author']);
        Book::create(['title' => 'Anonymous book', 'rating' => 1]);

        $authors = User::has('books')->get();
        $this->assertCount(2, $authors);
        $this->assertEquals('George R. R. Martin', $authors[0]->name);
        $this->assertEquals('John Doe', $authors[1]->name);

        $authors = User::has('books', '>', 1)->get();
        $this->assertCount(1, $authors);

        $authors = User::has('books', '<', 5)->get();
        $this->assertCount(3, $authors);

        $authors = User::has('books', '>=', 2)->get();
        $this->assertCount(1, $authors);

        $authors = User::has('books', '<=', 1)->get();
        $this->assertCount(2, $authors);

        $authors = User::has('books', '=', 2)->get();
        $this->assertCount(1, $authors);

        $authors = User::has('books', '!=', 2)->get();
        $this->assertCount(2, $authors);

        $authors = User::has('books', '=', 0)->get();
        $this->assertCount(1, $authors);

        $authors = User::has('books', '!=', 0)->get();
        $this->assertCount(2, $authors);

        $authors = User::whereHas('books', function ($query) {
            $query->where('rating', 5);
        })->get();
        $this->assertCount(1, $authors);

        $authors = User::whereHas('books', function ($query) {
            $query->where('rating', '<', 5);
        })->get();
        $this->assertCount(1, $authors);
    }

    public function testHasOneHas()
    {
        $user1 = User::create(['name' => 'John Doe']);
        $user1->role()->create(['title' => 'admin']);
        $user2 = User::create(['name' => 'Jane Doe']);
        $user2->role()->create(['title' => 'reseller']);
        User::create(['name' => 'Mark Moe']);
        Role::create(['title' => 'Customer']);

        $users = User::has('role')->get();
        $this->assertCount(2, $users);
        $this->assertEquals('John Doe', $users[0]->name);
        $this->assertEquals('Jane Doe', $users[1]->name);

        $users = User::has('role', '=', 0)->get();
        $this->assertCount(1, $users);

        $users = User::has('role', '!=', 0)->get();
        $this->assertCount(2, $users);
    }

    public function testNestedKeys()
    {
        $client = Client::create([
            'data' => [
                'client_id' => 35298,
                'name'      => 'John Doe',
            ],
        ]);

        $address = $client->addresses()->create([
            'data' => [
                'address_id' => 1432,
                'city'       => 'Paris',
            ],
        ]);

        $client = Client::where('data.client_id', 35298)->first();
        $this->assertEquals(1, $client->addresses->count());

        $address = $client->addresses->first();
        $this->assertEquals('Paris', $address->data['city']);

        $client = Client::with('addresses')->first();
        $this->assertEquals('Paris', $client->addresses->first()->data['city']);
    }

    public function testDoubleSaveOneToMany()
    {
        $author = User::create(['name' => 'George R. R. Martin']);
        $book = Book::create(['title' => 'A Game of Thrones']);

        $author->books()->save($book);
        $author->books()->save($book);
        $author->save();
        $this->assertEquals(1, $author->books()->count());
        $this->assertEquals($author->_id, $book->author_id);

        $author = User::where('name', 'George R. R. Martin')->first();
        $book = Book::where('title', 'A Game of Thrones')->first();
        $this->assertEquals(1, $author->books()->count());
        $this->assertEquals($author->_id, $book->author_id);

        $author->books()->save($book);
        $author->books()->save($book);
        $author->save();
        $this->assertEquals(1, $author->books()->count());
        $this->assertEquals($author->_id, $book->author_id);
    }

    public function testDoubleSaveManyToMany()
    {
        $user = User::create(['name' => 'John Doe']);
        $client = Client::create(['name' => 'Admins']);

        $user->clients()->save($client);
        $user->clients()->save($client);
        $user->save();

        //$this->assertEquals(1, $user->clients()->count());
        //$this->assertEquals([$user->_id], $client->user_ids);
        //$this->assertEquals([$client->_id], $user->client_ids);

        $user = User::where('name', 'John Doe')->first();
        $client = Client::where('name', 'Admins')->first();
        //$this->assertEquals(1, $user->clients()->count());
        //$this->assertEquals([$user->_id], $client->user_ids);
        //$this->assertEquals([$client->_id], $user->client_ids);

        $user->clients()->save($client);
        $user->clients()->save($client);
        $user->save();
        //$this->assertEquals(1, $user->clients()->count());
        //$this->assertEquals([$user->_id], $client->user_ids);
        //$this->assertEquals([$client->_id], $user->client_ids);
    }
}

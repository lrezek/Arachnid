1. OGM Theory
==============

An OGM, or "Object graph mapper" is a database layer that goes between your code and actual graph database calls. If you have used Doctrine or another ORM, you can think of an OGM as the same thing, but for graph databases. Basically, an OGM maps graph items, such as relationships and nodes (or edges and vertices), to real objects you define in your code. This allows you to define objects and use them regularly, while the OGM deals with all the actual database management.

For example, let's say you define a basic user class, with a name and password:

```PHP
class user
{
    public $name;
    public $password;
}
```

Normally, to get this class into a graph database, you would have to pass it to a method that communicates with the database and creates a node (or vertex) with the specified parameters. Now, let's say you wanted to change the user's password. In order to accomplish this, you would have to query the database for the user, then do another query to make the change. As you can imagine, writing methods like that take up a whole lot of time, and they make your code more difficult to read.

This is where an OGM comes into play. Because an OGM maps your objects to graph objects, you no longer have to worry about writing those tedious update queries. In the case of this OGM, you can redefine the above user class as follows:

```PHP
/**
 * @OGM\Node
 */
class user
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $name;

    /**
     * @OGM\Property
     */
    protected $password;
}
```

This is called an Entity, or an object that is mapped to a graph element. This class can be used in the same way as the regular user class defined above. However, if you pass that user object to the Entity Manager (discussed later), it keeps track of it for you and is capable of updating the object, querying for it, writing it to the database, or removing it from the database. This prevents you from having to get your hands dirty with query code and allows you to focus on the business logic of the application. You'll notice that the database parameters of the object, such as its primary key, properties, and indexes are defined through annotations in the actual class file. This means that as you define your objects you decide on their database attributes, and you never have to worry about them again.

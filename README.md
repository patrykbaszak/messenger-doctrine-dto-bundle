# Messenger-Doctrine-DTO-Bundle #

## Funkcjonalności i przykłady użytkowania ##
<br/>

### **Mapowanie właściwości DTO na parametry konstruktora encji** ###

W poniższym przykładzie `email` oraz `passwordHash` będą mapowane na parametry `email` oraz `passwordHash` w konstruktorze encji, a do jego ustawienia zostanie użyta ekspresja `new $entityClass(...['email' => $dto->email, 'passwordHash' => $dto->passwordHash])`.
```php
# dto:
#[TargetEntity(User::class)]
class UserRegistrationData
{
    public readonly string $email;
    public readonly string $passwordHash;
}

# entity:
#[ORM\Entity]
class User
{
    #[ORM\Column]
    private string $email;

    #[ORM\Column]
    private string $passwordHash;

    public function __construct(
        string $email,
        string $passwordHash
    ) {
        $this->email = $email;
        $this->passwordHash = $passwordHash;
    }
}
```
<br/>

### **Mapowanie właściwości DTO na parametry konstruktora oraz właściwości encji** ###

W poniższym przykładzie `email` oraz `passwordHash` będą mapowane na parametry `email` oraz `passwordHash` w konstruktorze encji, z kolei `firstname` będzie mapowane bezpośrednio na właściwość `firstname` w encji. Do ich ustawienia zostanie użyta ekspresja `$entity = new $entityClass(...['email' => $dto->email, 'passwordHash' => $dto->passwordHash]); $entity->setFirstname($dto->firstname);`.
```php
# dto:
#[TargetEntity(User::class)]
class UserRegistrationData
{
    public readonly string $email;
    public readonly string $passwordHash;
    public readonly string $firstname;
}

# entity:
#[ORM\Entity]
class User
{
    #[ORM\Column]
    private string $email;

    #[ORM\Column]
    private string $passwordHash;

    #[ORM\Column]
    private string $firstname;

    public function __construct(
        string $email,
        string $passwordHash
    ) {
        $this->email = $email;
        $this->passwordHash = $passwordHash;
    }

    public function setFirstname(string $firstname)
    {
        $this->firstname = $firstname;
    }
}
```
<br/>

### **Mapowanie właściwości DTO na właściwości encji z użyciem metody setProperty()** ###

> Wspierane metody to setProperty($property) oraz property($property)

W poniższym przykładzie `email` będzie mapowany bezpośrednio na pole `email` w encji, a do jego ustawienia zostanie użyta metoda `$entity->setEmail($dto->email)`.
```php
# dto:
#[TargetEntity(User::class)]
class UserRegistrationData
{
    public readonly string $email;
}

# entity:
#[ORM\Entity]
class User
{
    #[ORM\Column]
    private string $email;

    public function setEmail(string $email)
    {
        $this->email = $email;
    }
}
```
<br/>

### **Mapowanie prywatnych właściwości DTO na właściwości encji z getProperty()** ###

> Wspierane metody to getProperty() oraz property()
>
> **Dodatkowo**: jeśli mapper może wybrać pomiędzy metodą setProperty, a przypisaniem bezpośrednim to wybierze przypisanie bezpośrednie zamiast metody setProperty. 

W poniższym przykładzie `sex` będzie mapowany bezpośrednio na pole `sex` w encji, a do jego ustawienia zostanie użyta ekspresja `$entity->sex=$dto->getSex()`.
```php
# dto:
#[TargetEntity(User::class)]
class UserRegistrationData
{
    private string $sex;

    public function getSex(): string
    {
        return $this->sex;
    }
}

# entity:
#[ORM\Entity]
class User
{
    #[ORM\Column]
    public string $sex;

    public function setSex(string $sex)
    {
        $this->sex = $sex;
    }
}
```
<br/>

### **Mapowanie właściwości DTO na ręcznie wskazane właściwości encji** ###

W poniższym przykładzie `name` będzie mapowany na pole `firstname` w encji, a do jego ustawienia zostanie użyta ekspresja `$entity->firstname=$dto->name`.
```php
# dto:
#[TargetEntity(User::class)]
class UserRegistrationData
{
    #[TargetProperty('firstname')]
    public readonly string $name;
}

# entity:
#[ORM\Entity]
class User
{
    #[ORM\Column]
    public string $firstname;
}
```
<br/>

### **Mapowanie właściwości DTO na właściwości encji, które są zagnieżdżonymi encjami** ###

W poniższym przykładzie `authorId` będzie mapowany na właściwość `author` w encji, a do jego ustawienia zostanie użyta ekspresja `$entity->author=$entityManager->find(User::class, $dto->authorId)`.
```php
# dto:
#[TargetEntity(Post::class)]
class NewPost
{
    #[TargetProperty('author', User::class)]
    public readonly string $authorId;
}

# entity:
#[ORM\Entity]
class Post
{
    #[ORM\Column]
    public User $author;
}
```

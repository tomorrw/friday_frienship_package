# Friday Friendship

Publish config and migrations:

```sh
php artisan vendor:publish --provider="Tomorrow\FridayFriendship\FridayFriendshipProvider"
```

Configure the published config in:

```
config/FridayFriendship.php
```

Finally, migrate the database to create the table:

```sh
php artisan migrate
```

---

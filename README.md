Example code for implementing automatic IndieAuth between two sites


## Example Requests

These are the requests a reader client would make in order to fetch a private feed

### Fetch a feed with no authentication

```
curl -i https://avocado.lol/blog/
```

Notice that there is a `WWW-Authenticate` header

```
www-authenticate: Bearer scope="read"
link: <https://avocado.lol/blog/token.php>; rel="token_endpoint"
```

### Tell the reader user's authorization endpoint to get a token

```
curl https://avocado.lol/user/auth.php \
  -d request_type=external_token \
  -d target_url=https://avocado.lol/z/ \
  -d scope=read
```

Response contains a `request_id`

```
{
  "request_id": "4e1bfd2dab9f64d7998f",
  "interval": 5
}
```

### Poll the authorization endpoint until the token is ready

```
curl https://avocado.lol/user/auth.php \
  -d grant_type=request_id \
  -d request_id=4e1bfd2dab9f64d7998f
```

Response contains an access token

```
{
  "token_type": "Bearer",
  "access_token": "7d29ec8bf3c2a3249e4a427806d41fc099a776bf",
  "scope": "read",
  "expires_in": "86400"
}
```

### Fetch the feed with the access token

```
curl https://avocado.lol/blog/ -H "Authorization: Bearer 7d29ec8bf3c2a3249e4a427806d41fc099a776bf"
```

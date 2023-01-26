# Workflows

## Delete runs by workflow name

```bash
gh run list --workflow 'redis-server-tests.yml' --limit 1000 --json databaseId \
  | jq '.[].databaseId' \
  | xargs -I % gh api --silent -X DELETE /repos/predis/predis/actions/runs/%
```

# Secret files - NEVER commit these to git!
# Generate strong passwords and place in ./secrets/
# Each file should contain only the secret value (no newlines)

# Example generation commands:
# openssl rand -base64 32 > secrets/mongodb_root_password.txt
# openssl rand -base64 32 > secrets/redis_password.txt
# echo "admin" > secrets/mongodb_root_username.txt
# echo "your-grafana-password" > secrets/grafana_admin_password.txt
#!/usr/bin/env python3
import re

def extract_user_data(sql_content):
    """Extract user data from SQL INSERT statements"""
    users = []
    
    # Pattern to match INSERT VALUES
    pattern = r"\(NULL,\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*[^,]*,\s*'[^']*',\s*\d+,\s*[^,]*,\s*'[^']*',\s*'[^']*'\)"
    
    matches = re.findall(pattern, sql_content)
    
    for i, match in enumerate(matches):
        name, email, username = match
        users.append({
            'index': i,
            'name': name,
            'email': email,
            'username': username,
            'original_line': match
        })
    
    return users

def find_duplicates(users):
    """Find duplicates by username or email"""
    seen_usernames = {}
    seen_emails = {}
    duplicates = []
    
    for user in users:
        username = user['username']
        email = user['email']
        
        # Check for duplicate username
        if username in seen_usernames:
            duplicates.append({
                'type': 'username',
                'value': username,
                'original_index': seen_usernames[username],
                'duplicate_index': user['index'],
                'original_user': users[seen_usernames[username]],
                'duplicate_user': user
            })
        else:
            seen_usernames[username] = user['index']
        
        # Check for duplicate email
        if email in seen_emails:
            duplicates.append({
                'type': 'email',
                'value': email,
                'original_index': seen_emails[email],
                'duplicate_index': user['index'],
                'original_user': users[seen_emails[email]],
                'duplicate_user': user
            })
        else:
            seen_emails[email] = user['index']
    
    return duplicates

def remove_duplicates_from_sql(sql_content):
    """Remove duplicate entries from SQL content"""
    lines = sql_content.split('\n')
    
    # Find lines with INSERT VALUES
    insert_lines = []
    other_lines = []
    
    for i, line in enumerate(lines):
        if '(NULL,' in line and '@mitrakab.go.id' in line or 'gmail.com' in line:
            insert_lines.append((i, line))
        else:
            other_lines.append((i, line))
    
    # Extract user data from insert lines
    users_data = []
    for line_num, line in insert_lines:
        # Extract user info using regex
        pattern = r"\(NULL,\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',.*?\)"
        match = re.search(pattern, line)
        if match:
            name, email, username = match.groups()
            users_data.append({
                'line_num': line_num,
                'line': line,
                'name': name,
                'email': email,
                'username': username
            })
    
    # Find duplicates
    seen_usernames = set()
    seen_emails = set()
    lines_to_remove = set()
    
    for user in users_data:
        username = user['username']
        email = user['email']
        
        # Check if username or email already seen (keep first occurrence)
        if username in seen_usernames or email in seen_emails:
            lines_to_remove.add(user['line_num'])
            print(f"Removing duplicate: {user['name']} (username: {username}, email: {email})")
        else:
            seen_usernames.add(username)
            seen_emails.add(email)
    
    # Remove duplicate lines
    cleaned_lines = []
    for i, line in enumerate(lines):
        if i not in lines_to_remove:
            cleaned_lines.append(line)
    
    return '\n'.join(cleaned_lines)

# Read the SQL file
with open('/Users/randihartono/Desktop/src/mitrapi_xg/laravel/database/db.sql', 'r', encoding='utf-8') as f:
    sql_content = f.read()

# Remove duplicates
cleaned_sql = remove_duplicates_from_sql(sql_content)

# Write cleaned SQL to new file
with open('/Users/randihartono/Desktop/src/mitrapi_xg/laravel/database/db_cleaned.sql', 'w', encoding='utf-8') as f:
    f.write(cleaned_sql)

print("Duplicates removed successfully!")
print("Cleaned file saved as: db_cleaned.sql")
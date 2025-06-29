#!/usr/bin/env python3
import re

def remove_username_duplicates(sql_content):
    """Remove duplicate entries based on username only, keeping first occurrence"""
    lines = sql_content.split('\n')
    
    seen_usernames = set()
    cleaned_lines = []
    removed_count = 0
    
    for line in lines:
        # Check if this is an INSERT line with user data
        if '(NULL,' in line and ("@mitrakab.go.id" in line or "@gmail.com" in line or "@yahoo.com" in line or "@gmai.com" in line or "@gemail.com" in line):
            # Extract username using regex
            pattern = r"\(NULL,\s*'[^']*',\s*'[^']*',\s*'([^']*)',.*?\)"
            match = re.search(pattern, line)
            
            if match:
                username = match.group(1)
                
                # If username already seen, skip this line (remove duplicate)
                if username in seen_usernames:
                    print(f"Removing duplicate username: {username}")
                    removed_count += 1
                    continue
                else:
                    seen_usernames.add(username)
        
        # Keep the line
        cleaned_lines.append(line)
    
    print(f"Total duplicates removed: {removed_count}")
    return '\n'.join(cleaned_lines)

# Read the SQL file
with open('/Users/randihartono/Desktop/src/mitrapi_xg/laravel/database/db.sql', 'r', encoding='utf-8') as f:
    sql_content = f.read()

# Remove duplicates
cleaned_sql = remove_username_duplicates(sql_content)

# Write cleaned SQL back to original file
with open('/Users/randihartono/Desktop/src/mitrapi_xg/laravel/database/db.sql', 'w', encoding='utf-8') as f:
    f.write(cleaned_sql)

print("Username duplicates removed successfully!")
print("Original file has been updated.")
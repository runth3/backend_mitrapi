#!/usr/bin/env python3
import re

def find_email_duplicates(sql_content):
    """Find duplicate entries based on email and list them for review"""
    lines = sql_content.split('\n')
    
    email_users = {}  # email -> list of users with that email
    
    for line_num, line in enumerate(lines):
        # Check if this is an INSERT line with user data
        if '(NULL,' in line and ("@mitrakab.go.id" in line or "@gmail.com" in line or "@yahoo.com" in line or "@gmai.com" in line or "@gemail.com" in line):
            # Extract name, email, and username using regex
            pattern = r"\(NULL,\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',.*?\)"
            match = re.search(pattern, line)
            
            if match:
                name, email, username = match.groups()
                
                if email not in email_users:
                    email_users[email] = []
                
                email_users[email].append({
                    'line_num': line_num + 1,
                    'name': name,
                    'email': email,
                    'username': username
                })
    
    # Find duplicates
    duplicates_found = False
    for email, users in email_users.items():
        if len(users) > 1:
            duplicates_found = True
            print(f"\n=== DUPLICATE EMAIL: {email} ===")
            for i, user in enumerate(users, 1):
                print(f"  {i}. Name: {user['name']}")
                print(f"     Username: {user['username']}")
                print(f"     Line: {user['line_num']}")
                print()
    
    if not duplicates_found:
        print("No duplicate emails found!")
    
    return email_users

# Read the SQL file
with open('/Users/randihartono/Desktop/src/mitrapi_xg/laravel/database/db.sql', 'r', encoding='utf-8') as f:
    sql_content = f.read()

# Find email duplicates
print("Searching for duplicate emails...")
email_users = find_email_duplicates(sql_content)
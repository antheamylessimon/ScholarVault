import re

file_path = r'c:\Users\anthe\Downloads\data.sql'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Remove the column definitions from the CREATE TABLE `users`
# We look for the columns specifically and remove their lines.
columns_to_remove = [
    r'  `profile_picture`.*?\n',
    r'  `pending_picture`.*?\n',
    r'  `picture_status`.*?\n',
    r'  `profile_pic`.*?\n',
    r'  `pending_profile_pic`.*?\n',
    r'  `profile_pic_status`.*?\n'
]

for col_regex in columns_to_remove:
    content = re.sub(col_regex, '', content)

# Also fix the comma from the line before (updated_at) if it exists before ) ENGINE=InnoDB
# The updated_at might end with a comma now if it was followed by these.
content = re.sub(r'(  `updated_at`.*?),\n(\) ENGINE=)', r'\1\n\2', content)

# 2. Fix the INSERT INTO `users` header columns
content = content.replace(', `profile_picture`, `pending_picture`, `picture_status`, `profile_pic`, `pending_profile_pic`, `profile_pic_status`', '')

# 3. For each value tuple under INSERT INTO `users`, remove the last 6 values.
# An INSERT tuple generally looks like: (1, 'str', ... , 'none'),
# We can use regex to find the INSERT INTO `users` block and manipulate the lines.
lines = content.split('\n')
in_insert = False
out_lines = []
for line in lines:
    if line.startswith("INSERT INTO `users`"):
        # it starts the block and the first value might be on the same line or next line.
        in_insert = True
        
    if in_insert and re.match(r'^\(\d+,.*?\),?$', line):
        # It's a row of values
        # We need to strip the last 6 comma-separated items before the closing parenthesis.
        # Find the last closing parenthesis
        end_idx = line.rfind(')')
        if end_idx != -1:
            base_tuple = line[:end_idx]
            suffix = line[end_idx:] # usually '),' or ');'
            
            # split by comma, but be careful with strings that have commas! 
            # In this dataset, there are no complex commas in these last columns since they are enums or nulls or basic strings. 
            # The columns are: NULL, NULL, 'approved', NULL, NULL, 'none'
            # Or similar.
            parts = base_tuple.rsplit(',', 6)
            if len(parts) == 7:
                # We stripped 6 items
                new_line = parts[0] + suffix
                out_lines.append(new_line)
                if suffix == ');':
                    in_insert = False
                continue
    elif in_insert and ');' in line and line.strip() == ');':
        in_insert = False
        out_lines.append(line)
        continue
    
    # If the INSERT INTO is on one line:
    if "INSERT INTO `users`" in line and "VALUES" in line:
        pass # we handled inner lines, wait! The lines in data.sql are single rows per line!
        
    out_lines.append(line)

final_content = '\n'.join(out_lines)
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(final_content)

print(f"Successfully cleaned data.sql")

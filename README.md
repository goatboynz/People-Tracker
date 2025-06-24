# Mediflower Visitor Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A Home Assistant addon that provides a comprehensive visitor management system designed specifically for medical facilities. Track visitors, capture photos, collect digital signatures, and manage custom form fields all from within your Home Assistant environment.

## Features

- **Dynamic Custom Fields**
  - Create and manage custom form fields through the admin interface
  - Set fields as required or optional
  - Support for various field types (text, number, email, phone, date)

- **Visitor Sign-In/Out**
  - Quick and easy sign-in process
  - Optional photo capture
  - Digital signature support
  - Custom terms acceptance
  - Efficient sign-out system

- **Security**
  - Secure admin interface
  - Password protection
  - Rate limiting for login attempts
  - XSS and SQL injection protection
  - Input sanitization

- **Media Management**
  - Photo capture using device camera
  - Digital signature pad
  - Secure storage of media files

## Installation

1. In Home Assistant, navigate to **Supervisor** → **Add-on Store**
2. Add this repository URL:
   ```
   https://github.com/goatboynz/People-Tracker
   ```
3. Find the "Mediflower Visitor Management" addon and click install
4. Configure the admin password in the addon configuration
5. Start the addon
6. Click "OPEN WEB UI" to access the visitor management system

## Configuration

### Addon Configuration

```yaml
admin_password: SetSomethingStrongHere  # Change this!
```

### Custom Fields

Access the admin panel at `http://your-home-assistant:8420/admin.php` to:
- Add/remove custom fields
- Set field requirements
- Change field order
- Configure field types

## Usage

### Visitor Sign-In Process
1. Open the visitor management interface
2. Fill in required information
3. Take photo (optional)
4. Provide signature (optional)
5. Accept terms
6. Submit

### Sign-Out Process
1. Type visitor name in sign-out search
2. Select matching visitor
3. Confirm sign-out

### Admin Functions
- View visitor logs
- Manage custom fields
- Configure system settings
- View photos and signatures

## Support

For issues and feature requests, please open an issue on GitHub.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Built for Home Assistant
- Created with security and privacy in mind
- Designed for medical facility compliance

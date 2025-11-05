# Popup Form

A Drupal module that provides configurable popup forms with webform integration and drag-and-drop field management.

## Features

- **Configurable Popup Forms**: Create unlimited popup forms with custom triggers
- **Webform Integration**: Seamlessly embed Webform module forms
- **Drag & Drop Ordering**: Reorder fields using intuitive drag-and-drop interface
- **Customizable Settings**: Control animations, dimensions, behaviors
- **Responsive Design**: Mobile-friendly popups that adapt to screen size
- **Accessibility**: Full keyboard navigation and screen reader support
- **Multiple Triggers**: Support for any CSS selector as popup trigger

## Requirements

- Drupal 10.0+ or Drupal 11.0+
- Webform module (recommended)

## Installation

1. Download and install the module using Composer:
   ```bash
   composer require drupal/popup_form
   ```

2. Enable the module:
   ```bash
   drush en popup_form
   ```

3. Configure your popup forms at:
   `Administration » Configuration » User Interface » Popup Form`

## Configuration

### Creating a Popup Form

1. Go to `Administration » Configuration » User Interface » Popup Form » Manage`
2. Click "Add Popup Form"
3. Configure the basic settings:
   - **Label**: Administrative label for the popup
   - **Trigger Selector**: CSS selector for elements that open the popup (e.g., `#open-signUp-UcDavis`)
   - **Popup Title**: Title displayed in the popup
   - **Webform**: Select a webform to embed

### Popup Settings

Customize popup behavior:
- **Dimensions**: Set width and height
- **Animation**: Choose from fade, slide, zoom effects
- **Behavior**: Control overlay, close button, escape key, click-outside-close
- **Auto Close**: Optionally auto-close after a delay

### Field Management

- Add custom fields before or after the webform
- Use drag-and-drop to reorder fields
- Preview popup forms before publishing

## Usage

### Basic Usage

1. Create a popup form in the admin interface
2. Set the trigger selector (e.g., `#my-popup-trigger`)
3. Add the trigger element to your content:
   ```html
   <button id="my-popup-trigger">Open Signup Form</button>
   ```

### Programmatic Usage

```php
// Load popup form manager
$popup_manager = \Drupal::service('popup_form.popup_manager');

// Get all active popup forms
$popup_forms = $popup_manager->getActivePopupForms();

// Render popup content
$content = $popup_manager->renderPopupContent($popup_form);
```

### JavaScript API

```javascript
// Open a popup programmatically
Drupal.popupForm.open('popup_id', {
  title: 'My Popup',
  content: '<p>Popup content</p>',
  settings: {
    width: '600px',
    animation: 'fadeIn'
  }
});

// Close a popup
Drupal.popupForm.close('popup_id');
```

## Theming

### CSS Classes

- `.popup-form-wrapper`: Main wrapper
- `.popup-overlay`: Background overlay
- `.popup-container`: Popup container
- `.popup-content`: Content area
- `.popup-title`: Title element
- `.popup-description`: Description area

### Custom Themes

Override default styles by setting `include_default_css: false` in settings and providing your own CSS.

## API

### Hooks

- `hook_popup_form_alter()`: Modify popup form configuration
- `hook_popup_form_content_alter()`: Alter rendered popup content

### Services

- `popup_form.popup_manager`: Main service for popup form management

## Development

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

### Testing

```bash
# Run PHPUnit tests
./vendor/bin/phpunit modules/contrib/popup_form/tests/

# Run coding standards
./vendor/bin/phpcs --standard=Drupal modules/contrib/popup_form/
```

## License

This project is licensed under the GPL-2.0+ License.

## Support

- Issue Queue: https://www.drupal.org/project/issues/popup_form
- Documentation: https://www.drupal.org/docs/contributed-modules/popup-form
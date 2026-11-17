// Minimal reset so native checkboxes/radios pick up the brand colour without @tailwindcss/forms.
export default function ({ addBase }) {
    addBase({
        "input[type='checkbox'], input[type='radio']": { accentColor: '#0874B9', width: '1rem', height: '1rem' },
    });
}

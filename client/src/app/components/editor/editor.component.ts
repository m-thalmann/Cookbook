import { Component, ElementRef, forwardRef, Input, ViewChild } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

@Component({
  selector: 'cb-editor',
  templateUrl: './editor.component.html',
  styleUrls: ['./editor.component.scss'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => EditorComponent),
      multi: true,
    },
  ],
})
export class EditorComponent implements ControlValueAccessor {
  @ViewChild('content', { static: true }) content!: ElementRef;

  @Input() disabled: boolean = false;
  @Input() placeholder: string | null = null;

  propagateChange = (_: any) => {};

  value: string | null = '';
  private lastValue: string | null = '';

  constructor() {}

  writeValue(obj: any) {
    this.value = obj;
    this.lastValue = obj;
  }

  registerOnChange(fn: any) {
    this.propagateChange = fn;
  }

  registerOnTouched(fn: any) {}

  setDisabledState(isDisabled: boolean) {
    this.disabled = isDisabled;
  }

  get isEmpty() {
    return this.lastValue === null || this.lastValue === '';
  }

  toggleBold() {
    document.execCommand('bold', false, '');
    this.focus();
    this.update();
  }
  toggleItalic() {
    document.execCommand('italic', false, '');
    this.focus();
    this.update();
  }
  toggleUnderline() {
    document.execCommand('underline', false, '');
    this.focus();
    this.update();
  }

  toggleUnorderedList() {
    document.execCommand('insertUnorderedList', false, '');
    this.focus();
    this.update();
  }
  toggleOrderedList() {
    document.execCommand('insertOrderedList', false, '');
    this.focus();
    this.update();
  }

  addLink() {
    let link = window.prompt('Enter link');

    if (link) {
      document.execCommand('createLink', false);
    }
    this.focus();
    this.update();
  }
  removeLink() {
    document.execCommand('unlink', false, '');
    this.focus();
    this.update();
  }

  clearFormattings() {
    document.execCommand('removeFormat', false, '');
    this.focus();
    this.update();
  }

  keydown(event: Event) {
    if (event instanceof KeyboardEvent) {
      if (event.key === 'Tab') {
        event.preventDefault();
      } else {
        return;
      }

      if (!event.shiftKey) {
        document.execCommand('indent', false, '');
      } else {
        document.execCommand('outdent', false, '');
      }
      this.update();
    }
  }

  private focus() {
    this.content.nativeElement.focus();
  }

  update() {
    let value: string = this.content.nativeElement.innerHTML;
    value = value.trim();
    value = value.replace(/^(?:<br>)*|(?:<br>)*$|(?:<div><br><\/div>)*$/gm, '');

    if (value === this.lastValue) {
      return;
    }

    this.lastValue = value;
    this.propagateChange(value);
  }
}

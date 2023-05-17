import { coerceBooleanProperty } from '@angular/cdk/coercion';
import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, ElementRef, Input, SecurityContext, ViewChild } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';
import { DomSanitizer } from '@angular/platform-browser';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject } from 'rxjs';

@Component({
  selector: 'app-editor',
  standalone: true,
  imports: [CommonModule, MatTooltipModule, MatIconModule, MatButtonModule, TranslocoModule],
  templateUrl: './editor.component.html',
  styleUrls: ['./editor.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: EditorComponent,
      multi: true,
    },
  ],
})
export class EditorComponent implements ControlValueAccessor {
  @ViewChild('content', { static: true }) content!: ElementRef;

  @Input() placeholder: string | null = null;

  value: string | null = '';
  private currentValue: string | null = '';

  set disabled(disabled: any) {
    this.disabled$.next(coerceBooleanProperty(disabled));
  }

  disabled$ = new BehaviorSubject<boolean>(false);

  private onChange = (_: string | null) => {};
  private onTouched = () => {};

  constructor(private domSanitizer: DomSanitizer) {}

  get isEmpty() {
    return !this.currentValue;
  }

  writeValue(value: string) {
    this.value = value;
    this.currentValue = value;
  }

  registerOnChange(fn: (value: string | null) => void) {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void) {
    this.onTouched = fn;
  }

  setDisabledState(disabled: boolean): void {
    this.disabled = disabled;
  }

  // TODO: use modern approach to make text bold etc.

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
  async addLink() {
    // TODO: use dialog component
    let link = window.prompt('Enter link');

    if (link) {
      document.execCommand('createLink', false, link);
    }
    this.focus();
    this.update();
  }
  removeLink() {
    document.execCommand('unlink', false, '');
    this.focus();
    this.update();
  }
  clearFormatting() {
    document.execCommand('removeFormat', false, '');
    this.focus();
    this.update();
  }

  keydown(event: KeyboardEvent) {
    if (event.key !== 'Tab') {
      return;
    }

    event.preventDefault();

    if (!event.shiftKey) {
      document.execCommand('indent', false, '');
    } else {
      document.execCommand('outdent', false, '');
    }

    this.update();
  }

  blur() {
    this.onTouched();
  }

  update() {
    if (this.disabled) {
      return;
    }

    let value: string | null = this.content.nativeElement.innerHTML;

    if (value) {
      value = value.trim();
      value = value.replace(/^(?:<br>)*|(?:<br>)*$|(?:<div><br><\/div>)*$/gm, '');
      value = this.domSanitizer.sanitize(SecurityContext.HTML, value);
    }

    if (!value) {
      value = null;
    }

    if (this.currentValue === value) {
      return;
    }

    this.currentValue = value;
    this.onChange(value);
  }

  private focus() {
    this.content.nativeElement.focus();
  }
}


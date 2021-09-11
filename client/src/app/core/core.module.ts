import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MaterialModule } from './material/material.module';
import { AmountPipe } from './pipes/amount.pipe';
import { InputFocusDirective } from './directives/input-focus.directive';
import { TranslationPipe } from './i18n/translation.pipe';
import { LocalizedDatePipe } from './pipes/localized-date.pipe';

const declarations = [AmountPipe, TranslationPipe, LocalizedDatePipe, InputFocusDirective];

@NgModule({
  declarations: declarations,
  exports: [MaterialModule, ...declarations],
  imports: [CommonModule],
})
export class CoreModule {}

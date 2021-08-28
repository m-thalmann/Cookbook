import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MaterialModule } from './material/material.module';
import { AmountPipe } from './pipes/amount.pipe';
import { InputFocusDirective } from './directives/input-focus.directive';

const declarations = [AmountPipe, InputFocusDirective];

@NgModule({
  declarations: declarations,
  exports: [MaterialModule, ...declarations],
  imports: [CommonModule],
})
export class CoreModule {}

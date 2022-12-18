import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MaterialModule } from './material/material.module';
import { AmountPipe } from './pipes/amount.pipe';
import { ClampArrayPipe } from './pipes/clamp-array.pipe';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

const declarations = [AmountPipe, ClampArrayPipe];

@NgModule({
  declarations: declarations,
  exports: [MaterialModule, FormsModule, ReactiveFormsModule, ...declarations],
  imports: [CommonModule],
})
export class CoreModule {}

	define SNA_FILENAME "test.sna"
	define TRD_FILENAME "test.trd"
	define BIN_FILENAME "test.bin"

	device zxspectrum128

	;define _debug_border=1
	%if_anima%define _anima
	%if_music%define _play_music
	
	page 0
	
	org #6000
page0s	di : ld sp, $-2
	ld a, %border_color% : out (#fe), a 

	ld hl,#4000, de,#4001, bc,#1800, (hl), l : ldir
	ld hl,#5800, de,#5801, bc,#2ff, (hl),%main_color% : ldir
	
	ifdef _play_music
	; init PT3 player
	call PT3PLAY
	endif
	
	; init im2
	ld a,#5c, i,a, hl,interr, (#5cff),hl : im 2
	ei

	; main cicle
main	halt
	ifdef _anima
	call anima
	ld a, (CUR_PAGE) : call set_page ; restore page
	endif
	
	jr main
	
	ifdef _anima
anima	ld a, #01 : dec a : ld (anima + 1), a : ret nz	; animation pause
anima_p	ld hl, anima_proc
	ld a, (hl) : or a : jr nz, 1f
	ld hl, anima_proc + 4
1	ld a, (hl) : ld (anima + 1), a
	
	; set page
	inc hl : ld a, (hl) : call set_page

	inc hl : ld e, (hl)
	inc hl : ld d, (hl)
	inc hl : ld (anima_p + 1), hl
	ex de, hl

	; determine data flow start
	push hl
1	ld a, (hl) : inc hl : cp %11111111
	jr nz, 1b
	pop ix

	; set start address
	ld a, (hl) : ld e, a : inc hl
	ld a, (hl) : ld d, a : inc hl
_an_cycle	ld a, (ix + 0) : inc ix
	bit 7, a : jr z, 1f
	ld c, a
	and #e0
	cp #a0 : jr z, _anc_jmp100
	ret 
	; end of frame		
1	bit 6, a : jr z, _anc_set
_anc_jmp	; jump screen address
	and #3f
	inc a : ld c, a : ld b, 0
	ex de, hl : add hl, bc : ex de, hl
	jr _an_cycle
_anc_jmp100	ld a, c
	and #0f
	or a : jr z, 1f
	inc d : dec a : jr nz, $-2
1	ld a, c : and %00010000 : jr z, _an_cycle
	ld bc, #0080	; additional jump +128 bytes
	ex de, hl : add hl, bc : ex de, hl
	jr _an_cycle
_anc_set	; copy N bytes from flow to screen
	inc a
	ldi : dec a : jr nz, $-3
	jr _an_cycle
	display /d, 'Anima player size: ', $-anima+1 ; bear in mind db #00
anima_proc
%anima_frames% 
	db #00
	endif
	
interr	di
	push af,bc,de,hl,ix,iy
	exx : ex af, af'
	push af,bc,de,hl,ix,iy

	ifdef _debug_border : ld a, #01 : out (#fe), a : endif ; debug

	ifdef _play_music
	call PT3PLAY + 5
	ld a, (PT3PLAY + 11) : ld (CUR_PATTERN), a
	endif

	pop iy,ix,hl,de,bc,af
	exx : ex af, af'
	pop iy,ix,hl,de,bc,af
	ei
	ret

set_page	ld (CUR_PAGE), a : ld bc,#7ffd : out(c), a : ret
	
CUR_PATTERN	db #00
CUR_PAGE	db #00

	ifdef _play_music	
	align #100
PT3PLAY	include "sources/PTxPlay.asm"
	display /d, 'PT3 player size: ', $-PT3PLAY
	incbin "res/music"
	endif
	
	ifdef _anima
%anima_includes%
	endif

;	display /d, 'Main free space: ', #ffff-$

	; --------------------------
	include "sources/builder.asm"